import { Head } from '@inertiajs/react';
import { index } from '@/routes/billing/invoices';

type Invoice = {
    id: number;
    number: string;
    amount_cents: number;
    status: string;
    issued_at: string;
    owner: { name: string; email: string } | null;
};

const currency = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

export default function Invoices({ invoices }: { invoices: Invoice[] }) {
    return (
        <>
            <Head title="Invoices" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-baseline justify-between">
                    <h1 className="text-xl font-semibold">Invoices</h1>
                    <p className="text-sm text-muted-foreground">
                        {invoices.length}{' '}
                        {invoices.length === 1 ? 'invoice' : 'invoices'}
                    </p>
                </div>

                {invoices.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-8 text-center text-sm text-muted-foreground">
                        No invoices yet.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Number
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Owner
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Issued
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {invoices.map((invoice) => (
                                    <tr
                                        key={invoice.id}
                                        className="border-t border-sidebar-border/70"
                                    >
                                        <td className="px-4 py-3 font-mono">
                                            {invoice.number}
                                        </td>
                                        <td className="px-4 py-3">
                                            {invoice.owner === null ? (
                                                <span className="text-muted-foreground">
                                                    Unknown user
                                                </span>
                                            ) : (
                                                <>
                                                    <div>
                                                        {invoice.owner.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {invoice.owner.email}
                                                    </div>
                                                </>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {invoice.issued_at}
                                        </td>
                                        <td className="px-4 py-3 capitalize">
                                            {invoice.status}
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums">
                                            {currency.format(
                                                invoice.amount_cents / 100,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

Invoices.layout = {
    breadcrumbs: [
        {
            title: 'Invoices',
            href: index(),
        },
    ],
};
