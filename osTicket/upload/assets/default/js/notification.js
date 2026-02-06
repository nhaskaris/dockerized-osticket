/*!
 * Notification library - Pure JavaScript notification/toast popups
 * License: MIT
 */

const Notification = function(options = {}) {
  const defaults = {
    position: 'top-right',
    duration: 5000,
    isHidePrev: false,
    isHideTitle: false,
    maxOpened: 5
  };

  let settings = Object.assign({}, defaults, options);
  let notifications = [];
  let container = null;

  const positionClass = {
    'top-right': 'notification-top-right',
    'top-left': 'notification-top-left',
    'bottom-right': 'notification-bottom-right',
    'bottom-left': 'notification-bottom-left',
    'center': 'notification-center'
  };

  const getContainer = () => {
    if (!container) {
      container = document.createElement('div');
      container.className = `notification-container ${positionClass[settings.position] || 'notification-top-right'}`;
      document.body.appendChild(container);
    }
    return container;
  };

  const createNotification = (type, data) => {
    if (settings.isHidePrev && notifications.length > 0) {
      notifications.forEach(notif => {
        if (notif.element && notif.element.parentNode) {
          notif.element.parentNode.removeChild(notif.element);
        }
      });
      notifications = [];
    }

    if (notifications.length >= settings.maxOpened) {
      const oldNotif = notifications.shift();
      if (oldNotif.element && oldNotif.element.parentNode) {
        oldNotif.element.parentNode.removeChild(oldNotif.element);
      }
    }

    const element = document.createElement('div');
    element.className = `notification notification-${type}`;

    let html = '';
    if (!settings.isHideTitle && data.title) {
      html += `<div class="notification-title">${data.title}</div>`;
    }
    html += `<div class="notification-message">${data.message}</div>`;

    if (settings.isHideTitle && settings.duration === 0) {
      html += '<button class="notification-close">&times;</button>';
    }

    element.innerHTML = html;

    const closeBtn = element.querySelector('.notification-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        removeNotification(element);
      });
    }

    const cont = getContainer();
    cont.appendChild(element);

    const notifObj = { element, timeout: null, type };
    notifications.push(notifObj);

    if (settings.duration > 0) {
      notifObj.timeout = setTimeout(() => {
        removeNotification(element);
      }, settings.duration);
    }

    return notifObj;
  };

  const removeNotification = (element) => {
    const index = notifications.findIndex(n => n.element === element);
    if (index !== -1) {
      const notif = notifications[index];
      if (notif.timeout) clearTimeout(notif.timeout);
      if (element.parentNode) {
        element.parentNode.removeChild(element);
      }
      notifications.splice(index, 1);
    }
  };

  return {
    error: (data) => createNotification('error', data),
    warning: (data) => createNotification('warning', data),
    info: (data) => createNotification('info', data),
    success: (data) => createNotification('success', data),
    dialog: (data) => {
      const element = document.createElement('div');
      element.className = 'notification notification-dialog';

      let html = '';
      if (!settings.isHideTitle && data.title) {
        html += `<div class="notification-title">${data.title}</div>`;
      }
      html += `<div class="notification-message">${data.message}</div>`;
      html += '<div class="notification-actions">';
      html += '<button class="btn-ok">Ok</button>';
      html += '<button class="btn-cancel">Cancel</button>';
      html += '</div>';

      element.innerHTML = html;

      const okBtn = element.querySelector('.btn-ok');
      const cancelBtn = element.querySelector('.btn-cancel');

      okBtn.addEventListener('click', () => {
        if (!data.validFunc || data.validFunc()) {
          removeNotification(element);
          if (data.callback) data.callback(true);
        }
      });

      cancelBtn.addEventListener('click', () => {
        removeNotification(element);
        if (data.callback) data.callback(false);
      });

      const cont = getContainer();
      cont.appendChild(element);

      const notifObj = { element, timeout: null, type: 'dialog' };
      notifications.push(notifObj);

      return notifObj;
    },
    hide: (element) => removeNotification(element),
    setProperty: (newOptions) => {
      settings = Object.assign({}, settings, newOptions);
      if (container && newOptions.position) {
        container.className = `notification-container ${positionClass[newOptions.position] || 'notification-top-right'}`;
      }
    }
  };
};

// Export for ES modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = Notification;
}
