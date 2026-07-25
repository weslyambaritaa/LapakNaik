const STATUS_META = {
    pending: { label: 'Menunggu Pembayaran', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' },
    completed: { label: 'Selesai', class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' },
    expired: { label: 'Kedaluwarsa', class: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' },
    refunded: { label: 'Direfund', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' },
};

export function transactionStatusMeta(status) {
    return STATUS_META[status] ?? { label: status, class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' };
}

export const channelLabels = { pos: 'Kasir', online: 'Online' };
