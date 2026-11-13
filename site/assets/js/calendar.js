(function () {
    "use strict";

    const config = window.FLOWUP_CALENDAR;
    const modal = document.getElementById("event-modal");
    const form = document.getElementById("event-form");
    const calendarTitle = document.getElementById("calendar-title");
    const allDayInput = document.getElementById("event-all-day");
    const startInput = document.getElementById("event-start");
    const endInput = document.getElementById("event-end");
    const importFileInput = document.getElementById("calendar-import-file");
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
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (error) {
            throw new Error("A API do calendário não respondeu em JSON (HTTP " + response.status + "). Verifique a publicação do endpoint.");
        }
        if (!response.ok) {
            throw new Error(data && typeof data.error === "string" ? data.error : "Não foi possível concluir a solicitação.");
        }
        return data;
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
            event_type: properties.eventType || "event",
            is_completed: !!properties.isCompleted,
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

    function modalOpen(eventData, start, end, defaultAllDay, createType) {
        currentEvent = eventData || null;
        form.reset();
        document.getElementById("event-error").hidden = true;
        const eventType = eventData ? (eventData.extendedProps.eventType || "event") : (createType || "event");
        const typeLabels = { event: "evento", task: "tarefa", appointment: "agendamento" };
        document.getElementById("event-modal-title").textContent = eventData ? "Editar " + typeLabels[eventType] : "Novo " + typeLabels[eventType];
        document.getElementById("modal-eyebrow").textContent = eventData ? "DETALHES DO COMPROMISSO" : "ORGANIZE SEU TEMPO";
        document.getElementById("delete-event").hidden = !eventData;
        document.getElementById("duplicate-event").hidden = !eventData;
        document.getElementById("event-source-note").hidden = true;
        form.elements.event_type.value = eventType;
        document.getElementById("task-completed-field").hidden = eventType !== "task";
        document.getElementById("event-completed").checked = false;
        document.getElementById("save-event").textContent = eventType === "task" ? "Salvar tarefa" : (eventType === "appointment" ? "Salvar agendamento" : "Salvar evento");
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
            document.getElementById("event-completed").checked = data.is_completed;
            form.elements.guests.value = data.guests;
            form.elements.repeat_rule.value = data.repeat_rule;
            form.elements.reminder_minutes.value = data.reminder_minutes;
            form.elements.notes.value = data.notes;
        } else {
            const initialStart = start || new Date();
            const initialEnd = end || new Date(initialStart.getTime() + 3600000);
            form.elements.start.value = formDateTime(initialStart, allDay);
            form.elements.end.value = formDateTime(initialEnd, allDay);
            form.elements.color.value = "#5688ff";
        }
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
            event_type: form.elements.event_type.value,
            is_completed: document.getElementById("event-completed").checked,
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
        params.set("show_declined", document.getElementById("show-declined").checked ? "1" : "0");
        params.set("show_completed_tasks", document.getElementById("show-completed-tasks").checked ? "1" : "0");
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
            initialView: "timeGridWeek",
            views: {
                timeGridFourDay: { type: "timeGrid", duration: { days: 4 } }
            },
            locale: "pt-br",
            firstDay: 0,
            weekends: document.getElementById("show-weekends").checked,
            height: "auto",
            headerToolbar: false,
            eventDisplay: "block",
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
                document.querySelectorAll(".view-options button").forEach(function (button) {
                    button.classList.toggle("active", button.dataset.view === info.view.type);
                });
                const activeView = document.querySelector(".view-options button.active");
                if (activeView) document.getElementById("current-view-label").textContent = activeView.textContent.replace(/\s+[A-Z]$/, "").trim();
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
            eventContent: function (info) {
                if (!info.view.type.startsWith("timeGrid") && !info.view.type.startsWith("dayGrid")) {
                    return;
                }

                const content = document.createElement("div");
                content.className = "calendar-event-content";

                const title = document.createElement("span");
                title.className = "calendar-event-title";
                title.textContent = info.event.title;
                content.appendChild(title);

                if (!info.event.allDay && info.event.start) {
                    const time = document.createElement("span");
                    time.className = "calendar-event-time";
                    time.textContent = new Intl.DateTimeFormat("pt-BR", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hourCycle: "h23"
                    }).format(info.event.start);
                    content.appendChild(time);
                }

                return { domNodes: [content] };
            },
            eventClassNames: function (info) {
                const classes = [];
                if (info.event.extendedProps.isCompleted) classes.push("is-completed");
                if (info.event.extendedProps.isDeclined) classes.push("is-declined");
                return classes;
            },
            eventDidMount: function (info) {
                const details = [info.event.extendedProps.description];
                if (info.event.extendedProps.isCompleted) details.push("Tarefa concluída");
                if (info.event.extendedProps.isDeclined) details.push("Evento recusado");
                if (details.some(Boolean)) info.el.title = [info.event.title].concat(details.filter(Boolean)).join(" — ");
            }
        });
        calendar.render();
    }

    document.getElementById("calendar-prev").addEventListener("click", function () { calendar.prev(); });
    document.getElementById("calendar-next").addEventListener("click", function () { calendar.next(); });
    document.getElementById("calendar-today").addEventListener("click", function () { calendar.today(); });
    const viewToggle = document.getElementById("view-menu-toggle");
    const viewMenu = document.getElementById("view-menu");
    viewToggle.addEventListener("click", function () {
        viewMenu.hidden = !viewMenu.hidden;
        viewToggle.setAttribute("aria-expanded", String(!viewMenu.hidden));
    });
    document.querySelectorAll(".view-options button").forEach(function (button) {
        button.addEventListener("click", function () {
            calendar.changeView(button.dataset.view);
            viewMenu.hidden = true;
            viewToggle.setAttribute("aria-expanded", "false");
        });
    });
    document.getElementById("show-weekends").addEventListener("change", function (event) {
        calendar.setOption("weekends", event.target.checked);
    });
    document.getElementById("show-declined").addEventListener("change", function () { calendar.refetchEvents(); });
    document.getElementById("show-completed-tasks").addEventListener("change", function () { calendar.refetchEvents(); });

    const createToggle = document.getElementById("create-menu-toggle");
    const createMenu = document.getElementById("create-menu");
    createToggle.addEventListener("click", function () {
        createMenu.hidden = !createMenu.hidden;
        createToggle.setAttribute("aria-expanded", String(!createMenu.hidden));
    });
    document.querySelectorAll("[data-create-type]").forEach(function (button) {
        button.addEventListener("click", function () {
            createMenu.hidden = true;
            createToggle.setAttribute("aria-expanded", "false");
            modalOpen(null, null, null, false, button.dataset.createType);
        });
    });
    document.querySelectorAll("[data-close-modal]").forEach(function (button) {
        button.addEventListener("click", modalClose);
    });
    modal.addEventListener("click", function (event) { if (event.target === modal) modalClose(); });
    document.addEventListener("click", function (event) {
        if (!viewMenu.contains(event.target) && !viewToggle.contains(event.target)) {
            viewMenu.hidden = true;
            viewToggle.setAttribute("aria-expanded", "false");
        }
        if (!createMenu.contains(event.target) && !createToggle.contains(event.target)) {
            createMenu.hidden = true;
            createToggle.setAttribute("aria-expanded", "false");
        }
    });
    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") return;
        if (!modal.hidden) modalClose();
        viewMenu.hidden = true;
        viewToggle.setAttribute("aria-expanded", "false");
        createMenu.hidden = true;
        createToggle.setAttribute("aria-expanded", "false");
    });

    allDayInput.addEventListener("change", function () {
        const oldStart = startInput.value;
        const oldEnd = endInput.value;
        startInput.type = allDayInput.checked ? "date" : "datetime-local";
        endInput.type = allDayInput.checked ? "date" : "datetime-local";
        if (allDayInput.checked) {
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

    async function importCalendarFile(file) {
        const button = document.getElementById("import-calendar");
        const body = new FormData();
        body.append("calendar_file", file);
        button.disabled = true;
        try {
            const response = await fetch(config.api + "?action=import-file", {
                method: "POST",
                headers: { "Accept": "application/json", "X-CSRF-Token": config.csrf },
                body: body
            });
            const result = await response.json().catch(function () {
                throw new Error("O servidor não respondeu corretamente ao importar o arquivo.");
            });
            if (!response.ok) throw new Error(result.error || "Não foi possível importar o arquivo.");
            document.getElementById("category-filter").value = "";
            document.getElementById("event-search").value = "";
            if (result.first_date) {
                calendar.gotoDate(result.first_date);
            } else {
                calendar.refetchEvents();
            }
            const eventLabel = result.imported === 1 ? "evento" : "eventos";
            toast("Importação concluída: " + result.imported + " " + eventLabel + ".");
        } catch (error) {
            toast(error.message, true);
        } finally {
            button.disabled = false;
            importFileInput.value = "";
        }
    }

    async function exportCalendarFile() {
        const button = document.getElementById("export-calendar");
        button.disabled = true;
        try {
            const response = await fetch(config.api + "?action=export-file", {
                headers: { "Accept": "text/calendar" }
            });
            if (!response.ok) {
                const result = await response.json().catch(function () {
                    throw new Error("O servidor não respondeu corretamente ao exportar o calendário.");
                });
                throw new Error(result.error || "Não foi possível exportar o calendário.");
            }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = "flowup-calendar.ics";
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
            toast("Exportação concluída: " + (response.headers.get("X-Exported-Events") || "0") + " eventos.");
        } catch (error) {
            toast(error.message, true);
        } finally {
            button.disabled = false;
        }
    }

    function openCalendarImport() {
        closeCalendarActionsMenu();
        importFileInput.click();
    }

    const calendarActionsToggle = document.getElementById("calendar-actions-toggle");
    const calendarActionsDropdown = document.getElementById("calendar-actions-dropdown");

    function closeCalendarActionsMenu() {
        calendarActionsDropdown.hidden = true;
        calendarActionsToggle.setAttribute("aria-expanded", "false");
    }

    calendarActionsToggle.addEventListener("click", function () {
        const isExpanded = calendarActionsToggle.getAttribute("aria-expanded") === "true";
        calendarActionsDropdown.hidden = isExpanded;
        calendarActionsToggle.setAttribute("aria-expanded", String(!isExpanded));
    });

    document.addEventListener("click", function (event) {
        if (!event.target.closest(".calendar-actions-menu")) closeCalendarActionsMenu();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !calendarActionsDropdown.hidden) {
            closeCalendarActionsMenu();
            calendarActionsToggle.focus();
        }
    });

    document.getElementById("import-calendar").addEventListener("click", openCalendarImport);
    document.getElementById("export-calendar").addEventListener("click", exportCalendarFile);
    importFileInput.addEventListener("change", function () {
        if (importFileInput.files && importFileInput.files[0]) importCalendarFile(importFileInput.files[0]);
    });

    document.getElementById("category-filter").addEventListener("change", function () { calendar.refetchEvents(); });
    let searchTimeout;
    document.getElementById("event-search").addEventListener("input", function () {
        window.clearTimeout(searchTimeout);
        searchTimeout = window.setTimeout(function () { calendar.refetchEvents(); }, 250);
    });

    loadCategories().then(function () {
        renderCalendar();
    }).catch(function (error) {
        toast(error.message, true);
        renderCalendar();
    });
    if (config.flashError) toast(config.flashError, true);
})();
