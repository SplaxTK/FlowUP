(function () {
    "use strict";

    const config = window.FLOWUP_CALENDAR;
    const modal = document.getElementById("event-modal");
    const form = document.getElementById("event-form");
    const calendarTitle = document.getElementById("calendar-title");
    const syncIndicator = document.getElementById("sync-indicator");
    const allDayInput = document.getElementById("event-all-day");
    const startInput = document.getElementById("event-start");
    const endInput = document.getElementById("event-end");
    const timeInput = document.getElementById("event-time");
    let calendar;
    let categories = [];
    let currentEvent = null;
    let toastTimer;

    function toast(message, isError) {
        const existing = document.querySelector(".toast-message");
        if (existing) existing.remove();
        const element = document.createElement("div");
        element.className = "toast-message" + (isError ? " error" : "");
        element.setAttribute("role", "status");
        element.textContent = message;
        document.body.appendChild(element);
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () { element.remove(); }, 4200);
    }

    async function request(action, method, body) {
        const options = { method: method || "GET", headers: { "Accept": "application/json" } };
        if (body) {
            options.headers["Content-Type"] = "application/json";
            options.headers["X-CSRF-Token"] = config.csrf;
            options.body = JSON.stringify(body);
        }
        const response = await fetch(config.api + "?action=" + encodeURIComponent(action), options);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || "Não foi possível concluir a solicitação.");
        return data;
    }

    function setGoogleStatus(status) {
        const statusElement = document.getElementById("google-status");
        const sidebarElement = document.getElementById("google-sidebar-status");
        const connectButton = document.getElementById("google-connect");
        const syncButton = document.getElementById("sync-now");
        statusElement.classList.toggle("is-connected", !!status.connected);
        sidebarElement.parentElement.classList.toggle("is-connected", !!status.connected);
        if (status.connected) {
            statusElement.querySelector("span:last-child").textContent = status.account && status.account.email ? "Conectado: " + status.account.email : "Google Agenda conectado";
            sidebarElement.textContent = "Google Agenda conectado";
            connectButton.hidden = true;
            syncButton.hidden = false;
        } else {
            statusElement.querySelector("span:last-child").textContent = status.configured ? "Google Agenda desconectado" : "Google Agenda não configurado";
            sidebarElement.textContent = status.configured ? "Google Agenda desconectado" : "Google Agenda não configurado";
            connectButton.hidden = false;
            connectButton.disabled = !status.configured;
            connectButton.title = status.configured ? "" : "Configure as credenciais OAuth e a chave APP_ENCRYPTION_KEY no arquivo Site/.env";
            syncButton.hidden = true;
        }
    }

    async function loadStatus() {
        try {
            const data = await request("status");
            setGoogleStatus(data);
        } catch (error) {
            document.getElementById("google-status").querySelector("span:last-child").textContent = "Status indisponível";
            toast(error.message, true);
        }
    }

    async function loadCategories() {
        const data = await request("categories");
        categories = data.categories;
        const filter = document.getElementById("category-filter");
        const category = document.getElementById("event-category");
        categories.forEach(function (item) {
            const option = document.createElement("option");
            option.value = item.id;
            option.textContent = item.name;
            filter.appendChild(option);
            const formOption = option.cloneNode(true);
            category.appendChild(formOption);
        });
    }

    function eventPayload(event) {
        const properties = event.extendedProps;
        return {
            id: properties.parentEventId || event.id,
            title: event.title,
            description: properties.description || "",
            start: event.allDay ? dateOnly(event.start) : localDateTime(event.start),
            end: event.end ? (event.allDay ? dateOnly(event.end) : localDateTime(event.end)) : (event.allDay ? dateOnly(event.start) : localDateTime(new Date(event.start.getTime() + 3600000))),
            all_day: event.allDay,
            location: properties.location || "",
            category_id: properties.categoryId || "",
            color: properties.color || event.backgroundColor || "#5688ff",
            guests: properties.guests || "",
            repeat_rule: properties.repeatRule || "",
            reminder_minutes: properties.reminderMinutes === null ? "" : (properties.reminderMinutes || ""),
            notes: properties.notes || ""
        };
    }

    function dateOnly(date) {
        return date.getFullYear() + "-" + pad(date.getMonth() + 1) + "-" + pad(date.getDate());
    }

    function localDateTime(date) {
        return dateOnly(date) + "T" + pad(date.getHours()) + ":" + pad(date.getMinutes());
    }

    function pad(value) {
        return String(value).padStart(2, "0");
    }

    function formDateTime(date, allDay) {
        if (!date) return "";
        return allDay ? dateOnly(date) : localDateTime(date);
    }

    function modalOpen(eventData, start, end, defaultAllDay) {
        currentEvent = eventData || null;
        form.reset();
        document.getElementById("event-error").hidden = true;
        document.getElementById("event-modal-title").textContent = eventData ? "Editar evento" : "Novo evento";
        document.getElementById("modal-eyebrow").textContent = eventData ? "DETALHES DO EVENTO" : "ORGANIZE SEU TEMPO";
        document.getElementById("delete-event").hidden = !eventData;
        document.getElementById("duplicate-event").hidden = !eventData;
        document.getElementById("event-source-note").hidden = true;
        const allDay = eventData ? eventData.allDay : !!defaultAllDay;
        allDayInput.checked = allDay;
        startInput.type = allDay ? "date" : "datetime-local";
        endInput.type = allDay ? "date" : "datetime-local";
        if (eventData) {
            const data = eventPayload(eventData);
            form.elements.id.value = data.id;
            form.elements.title.value = data.title;
            form.elements.description.value = data.description;
            form.elements.start.value = data.start;
            form.elements.end.value = data.end;
            form.elements.location.value = data.location;
            form.elements.category_id.value = data.category_id;
            form.elements.color.value = data.color;
            form.elements.guests.value = data.guests;
            form.elements.repeat_rule.value = data.repeat_rule;
            form.elements.reminder_minutes.value = data.reminder_minutes;
            form.elements.notes.value = data.notes;
            if (eventData.extendedProps.googleSynced) {
                const source = document.getElementById("event-source-note");
                source.textContent = "Este evento está sincronizado com o Google Agenda.";
                source.hidden = false;
            }
        } else {
            const initialStart = start || new Date();
            const initialEnd = end || new Date(initialStart.getTime() + 3600000);
            form.elements.start.value = formDateTime(initialStart, allDay);
            form.elements.end.value = formDateTime(initialEnd, allDay);
            form.elements.color.value = "#5688ff";
        }
        timeInput.value = allDay ? "" : startInput.value.slice(11, 16);

        modal.hidden = false;
        document.body.classList.add("modal-aberto");
        document.getElementById("event-title").focus();
    }

    function modalClose() {
        modal.hidden = true;
        document.body.classList.remove("modal-aberto");
        currentEvent = null;
    }

    function formPayload() {
        return {
            id: form.elements.id.value || undefined,
            title: form.elements.title.value.trim(),
            description: form.elements.description.value.trim(),
            start: form.elements.start.value,
            end: form.elements.end.value,
            all_day: allDayInput.checked,
            location: form.elements.location.value.trim(),
            category_id: form.elements.category_id.value || null,
            color: form.elements.color.value,
            guests: form.elements.guests.value.trim(),
            repeat_rule: form.elements.repeat_rule.value,
            reminder_minutes: form.elements.reminder_minutes.value,
            notes: form.elements.notes.value.trim()
        };
    }

    function getEventFilters(info) {
        const params = new URLSearchParams({
            action: "events",
            start: info.startStr,
            end: info.endStr
        });
        const categoryId = document.getElementById("category-filter").value;
        const search = document.getElementById("event-search").value.trim();
        if (categoryId) params.set("category_id", categoryId);
        if (search) params.set("search", search);
        return config.api + "?" + params.toString();
    }

    async function updateEvent(event) {
        try {
            await request("update", "POST", eventPayload(event));
            toast("Evento atualizado.");
            calendar.refetchEvents();
        } catch (error) {
            event.revert();
            toast(error.message, true);
        }
    }

    function renderCalendar() {
        const calendarElement = document.getElementById("calendar");
        calendar = new FullCalendar.Calendar(calendarElement, {
            initialView: "dayGridMonth",
            locale: "pt-br",
            firstDay: 0,
            height: "auto",
            headerToolbar: false,
            dayMaxEvents: 3,
            nowIndicator: true,
            navLinks: true,
            selectable: true,
            selectMirror: true,
            editable: true,
            eventDurationEditable: true,
            events: function (info, success, failure) {
                fetch(getEventFilters(info), { headers: { "Accept": "application/json" } })
                    .then(function (response) { if (!response.ok) throw new Error("Não foi possível carregar seus eventos."); return response.json(); })
                    .then(success)
                    .catch(function (error) { toast(error.message, true); failure(error); });
            },
            datesSet: function (info) {
                calendarTitle.textContent = info.view.title;
                document.querySelectorAll(".calendar-views button").forEach(function (button) {
                    button.classList.toggle("active", button.dataset.view === info.view.type);
                });
            },
            dateClick: function (info) {
                const start = info.date;
                const end = new Date(start.getTime() + (info.allDay ? 86400000 : 3600000));
                modalOpen(null, start, end, info.allDay);
            },
            select: function (info) {
                modalOpen(null, info.start, info.end, info.allDay);
                calendar.unselect();
            },
            eventClick: function (info) { modalOpen(info.event); },
            eventDrop: function (info) { updateEvent(info.event); },
            eventResize: function (info) { updateEvent(info.event); },
            eventDidMount: function (info) {
                const description = info.event.extendedProps.description;
                if (description) info.el.title = info.event.title + " — " + description;
            }
        });
        calendar.render();
    }

    document.getElementById("calendar-prev").addEventListener("click", function () { calendar.prev(); });
    document.getElementById("calendar-next").addEventListener("click", function () { calendar.next(); });
    document.getElementById("calendar-today").addEventListener("click", function () { calendar.today(); });
    document.querySelectorAll(".calendar-views button").forEach(function (button) {
        button.addEventListener("click", function () { calendar.changeView(button.dataset.view); });
    });
    document.querySelectorAll("[data-new-event]").forEach(function (button) {
        button.addEventListener("click", function (event) { event.preventDefault(); modalOpen(); });
    });
    document.querySelectorAll("[data-close-modal]").forEach(function (button) {
        button.addEventListener("click", modalClose);
    });
    modal.addEventListener("click", function (event) { if (event.target === modal) modalClose(); });
    document.addEventListener("keydown", function (event) { if (event.key === "Escape" && !modal.hidden) modalClose(); });

    allDayInput.addEventListener("change", function () {
        const oldStart = startInput.value;
        const oldEnd = endInput.value;
        startInput.type = allDayInput.checked ? "date" : "datetime-local";
        endInput.type = allDayInput.checked ? "date" : "datetime-local";
        if (allDayInput.checked) {
            timeInput.value = "";
            startInput.value = oldStart.slice(0, 10);
            endInput.value = oldEnd.slice(0, 10);
            if (endInput.value <= startInput.value) {
                const nextDay = new Date(startInput.value + "T00:00:00");
                nextDay.setDate(nextDay.getDate() + 1);
                endInput.value = dateOnly(nextDay);
            }
        } else {
            startInput.value = oldStart.length === 10 ? oldStart + "T09:00" : oldStart;
            endInput.value = oldEnd.length === 10 ? oldEnd + "T10:00" : oldEnd;
            timeInput.value = startInput.value.slice(11, 16);
        }
    });

    timeInput.addEventListener("change", function () {
        if (!timeInput.value) return;
        if (allDayInput.checked) {
            const eventDate = startInput.value;
            allDayInput.checked = false;
            startInput.type = "datetime-local";
            endInput.type = "datetime-local";
            const start = new Date(eventDate + "T" + timeInput.value + ":00");
            startInput.value = localDateTime(start);
            endInput.value = localDateTime(new Date(start.getTime() + 3600000));
        } else {
            const start = new Date(startInput.value.slice(0, 10) + "T" + timeInput.value + ":00");
            startInput.value = localDateTime(start);
            if (!endInput.value || endInput.value <= startInput.value) {
                endInput.value = localDateTime(new Date(start.getTime() + 3600000));
            }
        }
    });

    startInput.addEventListener("change", function () {
        if (!allDayInput.checked && startInput.value.length > 10) {
            timeInput.value = startInput.value.slice(11, 16);
        }
    });

    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        const saveButton = document.getElementById("save-event");
        saveButton.disabled = true;
        try {
            const action = form.elements.id.value ? "update" : "create";
            await request(action, "POST", formPayload());
            modalClose();
            calendar.refetchEvents();
            toast(action === "create" ? "Evento criado com sucesso." : "Evento atualizado.");
        } catch (error) {
            const errorMessage = document.getElementById("event-error");
            errorMessage.textContent = error.message;
            errorMessage.hidden = false;
        } finally {
            saveButton.disabled = false;
        }
    });

    document.getElementById("delete-event").addEventListener("click", async function () {
        if (!currentEvent || !window.confirm("Deseja excluir este evento?")) return;
        try {
            await request("delete", "POST", { id: currentEvent.extendedProps.parentEventId || currentEvent.id });
            modalClose();
            calendar.refetchEvents();
            toast("Evento excluído.");
        } catch (error) { toast(error.message, true); }
    });

    document.getElementById("duplicate-event").addEventListener("click", async function () {
        if (!currentEvent) return;
        try {
            await request("duplicate", "POST", { id: currentEvent.extendedProps.parentEventId || currentEvent.id });
            modalClose();
            calendar.refetchEvents();
            toast("Evento duplicado para o dia seguinte.");
        } catch (error) { toast(error.message, true); }
    });

    document.getElementById("google-connect").addEventListener("click", function () {
        window.location.href = "google-oauth.php";
    });

    document.getElementById("sync-now").addEventListener("click", async function () {
        const button = document.getElementById("sync-now");
        syncIndicator.classList.add("syncing");
        syncIndicator.lastChild.textContent = "Sincronizando...";
        button.disabled = true;
        try {
            const result = await request("sync", "POST", {});
            setGoogleStatus({ connected: true, account: result.status, configured: true });
            calendar.refetchEvents();
            syncIndicator.lastChild.textContent = "Sincronizado agora";
            toast("Sincronização concluída: " + result.googleEvents + " eventos do Google.");
        } catch (error) {
            syncIndicator.lastChild.textContent = "Falha na sincronização";
            toast(error.message, true);
        } finally {
            syncIndicator.classList.remove("syncing");
            button.disabled = false;
        }
    });

    document.getElementById("category-filter").addEventListener("change", function () { calendar.refetchEvents(); });
    let searchTimeout;
    document.getElementById("event-search").addEventListener("input", function () {
        window.clearTimeout(searchTimeout);
        searchTimeout = window.setTimeout(function () { calendar.refetchEvents(); }, 250);
    });

    loadCategories().then(function () {
        renderCalendar();
        loadStatus();
    }).catch(function (error) {
        toast(error.message, true);
        renderCalendar();
    });
    if (config.flashError) toast(config.flashError, true);
})();
