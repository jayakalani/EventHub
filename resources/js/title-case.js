/**
 * Title-case formatting for explicitly marked human-readable inputs.
 * Mark fields with data-title-case (or data-title-case="true").
 * Never mark emails, passwords, phones, URLs, or IDs.
 */
function toTitleCase(value) {
    if (typeof value !== 'string') {
        return value;
    }

    const trimmed = value.trim().replace(/\s+/g, ' ');
    if (!trimmed) {
        return trimmed;
    }

    return trimmed
        .toLowerCase()
        .split(' ')
        .map((word) => word
            .split('-')
            .map((segment) => segment
                .split("'")
                .map((part) => (part ? part.charAt(0).toUpperCase() + part.slice(1) : part))
                .join("'"))
            .join('-'))
        .join(' ');
}

function bindTitleCaseFields(root = document) {
    root.querySelectorAll('[data-title-case]').forEach((el) => {
        if (el.dataset.titleCaseBound === '1') {
            return;
        }

        el.dataset.titleCaseBound = '1';

        const apply = () => {
            if (typeof el.value === 'string' && el.value.trim() !== '') {
                el.value = toTitleCase(el.value);
            }
        };

        el.addEventListener('blur', apply);
        el.addEventListener('change', apply);
    });
}

document.addEventListener('DOMContentLoaded', () => bindTitleCaseFields());

export { toTitleCase, bindTitleCaseFields };
