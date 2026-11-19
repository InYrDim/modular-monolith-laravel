<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Billing\Database\Factories\InvoiceFactory;

/**
 * @property int $id
 * @property int $user_id The owning user, resolved through Identity\Contracts\UserDirectory.
 * @property string $number
 * @property int $amount_cents
 * @property string $status
 * @property Carbon $issued_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'number', 'amount_cents', 'status', 'issued_at'])]
#[Table('billing_invoices')]
#[UseFactory(InvoiceFactory::class)]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'issued_at' => 'date',
        ];
    }
}
