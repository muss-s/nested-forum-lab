// Collapse/expand: buttons are created only when JS runs, so with JS off all replies stay visible.
document.querySelectorAll('.c[data-has-children]').forEach(function (c) {
  var box = document.getElementById('replies-' + c.dataset.id);
  if (!box) return;
  var b = document.createElement('button');
  b.type = 'button';
  b.setAttribute('aria-controls', box.id);
  function set(open) { box.hidden = !open; b.setAttribute('aria-expanded', String(open)); b.textContent = open ? 'Hide replies' : 'Show replies'; }
  b.addEventListener('click', function () { set(box.hidden); });
  set(true);
  c.appendChild(b);
});
