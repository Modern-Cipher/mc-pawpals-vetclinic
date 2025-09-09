/* assets/js/calendar-component.js
 * Tiny wrapper around FullCalendar that DOES NOT override caller config.
 * Your caller can pass its own `events`, `dateClick`, etc.
 */
window.createClinicCalendar = function createClinicCalendar({ element, config = {} }) {
  if (!element) throw new Error('createClinicCalendar: element is required');

  const defaults = {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },
    height: 'auto',
    navLinks: true,
    nowIndicator: true,
    eventTimeFormat: { hour: 'numeric', minute: '2-digit' }
  };

  // IMPORTANT: don't clobber caller's keys (especially `events`)
  const opts = Object.assign({}, defaults, config || {});
  const cal = new FullCalendar.Calendar(element, opts);
  cal.render();
  return cal;
};
