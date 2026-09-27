// manage-users.js — user table search, the create/edit account modals and the
// password generator (moved out of the page's inline <script> so the CSP does
// not need 'unsafe-inline').
(function () {
  const openModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = 'flex';
    modal.classList.remove('hidden');
  };

  const closeModal = (id) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = 'none';
    modal.classList.add('hidden');
  };

  const setValue = (id, value) => {
    const field = document.getElementById(id);
    if (field) field.value = value;
  };

  function filterUserTable(query) {
    const needle = String(query || '').toLowerCase().trim();
    document.querySelectorAll('.user-row').forEach(row => {
      row.style.display = (row.dataset.search || '').includes(needle) ? '' : 'none';
    });
  }

  function openEditUserModal(btn) {
    setValue('edit_user_id', btn.dataset.userId);
    setValue('edit_name', btn.dataset.userName || '');
    setValue('edit_email', btn.dataset.userEmail || '');
    setValue('edit_contact', btn.dataset.userContact || '');
    setValue('edit_role', btn.dataset.userRole || '');
    setValue('edit_password', '');
    openModal('editUserModal');
  }

  function generateRandomPass(targetId) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$';
    let pass = '';
    for (let i = 0; i < 10; i++) {
      pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    setValue(targetId, pass);
  }

  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-open-modal]');
    if (opener) { openModal(opener.dataset.openModal); return; }

    const editBtn = e.target.closest('[data-open-edit-user]');
    if (editBtn) { openEditUserModal(editBtn); return; }

    const closer = e.target.closest('[data-close-modal]');
    if (closer) { closeModal(closer.dataset.closeModal); return; }

    // Clicking the dimmed backdrop closes the modal, but clicks inside it don't.
    const overlay = e.target.closest('[data-dismiss-modal]');
    if (overlay && overlay === e.target) { closeModal(overlay.dataset.dismissModal); return; }

    const generator = e.target.closest('[data-generate-password]');
    if (generator) generateRandomPass(generator.dataset.generatePassword);
  });

  document.addEventListener('input', (e) => {
    if (e.target.matches('[data-filter-user-table]')) filterUserTable(e.target.value);
  });

  // Close modals on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal('createUserModal');
      closeModal('editUserModal');
    }
  });

  // Auto open modal if requested via URL parameter (?action=create or ?edit=ID)
  window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('action') === 'create') {
      openModal('createUserModal');
    } else if (params.get('edit')) {
      const btn = document.getElementById('edit-btn-' + params.get('edit'));
      if (btn) btn.click();
    }
  });
})();
