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

        if (select.value === 'postponed' && currentStatus !== 'postponed') {
            select.value = currentStatus;

            if (currentStatus !== 'upcoming') {
                window.alert('Only upcoming events can be postponed.');
                return;
            }

            if (!window.confirm('Are you sure you want to postpone this event?')) {
                return;
            }

            if (data) {
                data.postponeModal = {
                    open: true,
                    eventId: eventId,
                    action: (data.eventsBaseUrl || '') + '/' + eventId + '/postpone',
                    name: eventName,
                    date: select.dataset.eventDate || '',
                    time: select.dataset.eventTime || '',
                    place: select.dataset.eventPlace || '',
                };
            }
            return;
        }

        if (currentStatus === 'postponed' && select.value === 'upcoming') {
            select.value = currentStatus;
            window.alert('Postponed events cannot be changed to Upcoming. Status stays Postponed — set a new date/time, or cancel if the event will not happen.');
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
