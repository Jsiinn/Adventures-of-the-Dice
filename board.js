// board.js — Client-side enhancements

(function () {
  'use strict';

  // ── Roll button animation ────────────────────────────────────────
  const rollBtn = document.getElementById('roll-btn');
  const rollForm = document.getElementById('roll-form');

  if (rollBtn && rollForm) {
    rollForm.addEventListener('submit', function () {
      rollBtn.disabled = true;
      rollBtn.style.opacity = '0.7';
      rollBtn.innerHTML = '<span class="roll-dice" style="animation: spin 0.4s linear infinite">[dice]</span><span>Rolling…</span>';
    });
  }

  // ── Highlight active cell ────────────────────────────────────────
  const activeCell = document.querySelector('.cell-active');
  if (activeCell) {
    setTimeout(() => {
      activeCell.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
  }

  // ── Cell tooltips ────────────────────────────────────────────────
  document.querySelectorAll('.cell[data-cell]').forEach(cell => {
    cell.addEventListener('mouseenter', function (e) {
      const num = this.dataset.cell;
      const overlay = this.querySelector('.cell-overlay');
      if (overlay && overlay.title) {
        this.title = `Cell ${num}: ${overlay.title}`;
      }
    });
  });

  // ── Animated die face on last roll ──────────────────────────────
  const dieFace = document.querySelector('.die-face');
  if (dieFace) {
    dieFace.style.animation = 'diceRoll 0.4s ease-out';
  }

  // Inject keyframes dynamically
  const style = document.createElement('style');
  style.textContent = `
    @keyframes spin {
      from { transform: rotate(0deg); }
      to   { transform: rotate(360deg); }
    }
    @keyframes diceRoll {
      0%   { transform: scale(0.5) rotate(-20deg); opacity: 0; }
      60%  { transform: scale(1.2) rotate(5deg); }
      100% { transform: scale(1) rotate(0deg); opacity: 1; }
    }
  `;
  document.head.appendChild(style);

})();