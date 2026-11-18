import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

/**
 * Format a byte count as a short human-readable size (e.g. "1.4 MB").
 */
export function formatBytes(bytes) {
    if (!bytes && bytes !== 0) {
        return '';
    }
    if (bytes === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / 1024 ** exponent;

    return `${exponent === 0 ? value : value.toFixed(1)} ${units[exponent]}`;
}

