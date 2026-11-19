<?php

namespace Modules\Billing\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Models\Invoice;
use Modules\Identity\Contracts\Dto\UserSummary;
use Modules\Identity\Contracts\UserDirectory;

class InvoiceController
{
    public function __invoke(UserDirectory $users): Response
    {
        $invoices = Invoice::query()
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();

        $userIds = array_values($invoices
            ->map(fn (Invoice $invoice): int => $invoice->user_id)
            ->unique()
            ->all());

        $owners = $users->summaries($userIds);

        return Inertia::render('billing/invoices', [
            'invoices' => $invoices->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'amount_cents' => $invoice->amount_cents,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at->toDateString(),
                'owner' => $this->ownerSummary($owners[$invoice->user_id] ?? null),
            ])->all(),
        ]);
    }

    /**
     * @return array{name: string, email: string}|null
     */
    private function ownerSummary(?UserSummary $owner): ?array
    {
        return $owner === null ? null : ['name' => $owner->name, 'email' => $owner->email];
    }
}
