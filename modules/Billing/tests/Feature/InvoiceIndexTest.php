<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Billing\Models\Invoice;
use Modules\Identity\Models\User;

it('lists invoices with owners resolved through the identity contract', function () {
    $owner = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    Invoice::factory()->forUser($owner->id)->create([
        'number' => 'INV-000001',
        'amount_cents' => 12_500,
        'status' => 'paid',
        'issued_at' => '2026-01-15',
    ]);

    $this->actingAs($owner)
        ->get(route('billing.invoices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('billing/invoices')
            ->has('invoices', 1)
            ->where('invoices.0.number', 'INV-000001')
            ->where('invoices.0.amount_cents', 12_500)
            ->where('invoices.0.status', 'paid')
            ->where('invoices.0.issued_at', '2026-01-15')
            ->where('invoices.0.owner.name', 'Ada Lovelace')
            ->where('invoices.0.owner.email', 'ada@example.com'));
});

it('renders an invoice whose owner no longer exists without failing', function () {
    $viewer = User::factory()->create();

    Invoice::factory()->forUser(999_999)->create(['number' => 'INV-000002']);

    $this->actingAs($viewer)
        ->get(route('billing.invoices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices', 1)
            ->where('invoices.0.owner', null));
});

it('redirects guests to the login page', function () {
    $this->get(route('billing.invoices.index'))->assertRedirect(route('login'));
});
