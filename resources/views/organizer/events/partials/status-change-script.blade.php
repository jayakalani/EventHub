<script>
    window.organizerHandleEventStatusChange = function (select) {
        const root = select.closest('[x-data]');
        const data = root && window.Alpine ? window.Alpine.$data(root) : null;
        const eventId = select.dataset.eventId;
        const eventName = select.dataset.eventName || 'this event';
        const currentStatus = select.dataset.currentStatus;

        if (select.value === 'cancelled' && currentStatus !== 'cancelled') {
            select.value = currentStatus;
            if (data) {
                data.cancelModal = {
                    open: true,
                    eventId: eventId,
                    action: (data.eventsBaseUrl || '') + '/' + eventId + '/cancel',
                    name: eventName,
                    date: select.dataset.eventDate || '',
                    time: select.dataset.eventTime || '',
                    place: select.dataset.eventPlace || '',
                };
            }
            return;
        }

        if (currentStatus === 'cancelled' || select.value === currentStatus) {
            select.value = currentStatus;
            return;
        }

        const statusLabel = (select.options[select.selectedIndex] && select.options[select.selectedIndex].text.trim()) || select.value;
        if (!window.confirm('Are you sure you want to change the status of ' + eventName + ' to ' + statusLabel + '?')) {
            select.value = currentStatus;
            return;
        }

        select.form.submit();
    };
</script>
