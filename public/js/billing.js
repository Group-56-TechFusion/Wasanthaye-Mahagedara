/* Billing add / edit form behaviour */
(function () {
  var form = document.getElementById('bill-form');
  if (!form) return;

  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var val = function (name) { var el = form.elements[name]; return el ? parseFloat(el.value) || 0 : 0; };
  var fmt = function (n) { return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

  // ---------------------------------------------------------------- totals
  function installmentsTotal() {
    return [2, 3, 4].reduce(function (sum, n) { return sum + val('inst[' + n + '][amount]'); }, 0);
  }
  function total() { return Math.max(0, val('package_price') + val('transport') - val('discount')); }

  function recalc() {
    var t = total();
    var full = $('#full_payment');
    if (full && full.checked) {
      form.elements.advance_amount.value = Math.max(0, t - installmentsTotal()).toFixed(2);
    }
    var paid = val('advance_amount') + installmentsTotal();
    var remaining = t - paid;
    $('#total_display').value = fmt(t);
    $('#balance_display').value = fmt(remaining);
    var t2 = $('#total_display2'), r2 = $('#remaining_display');
    if (t2) t2.value = 'Rs ' + fmt(t);
    if (r2) r2.value = 'Rs ' + fmt(remaining);
  }
  ['package_price', 'transport', 'discount', 'advance_amount', 'inst[2][amount]', 'inst[3][amount]', 'inst[4][amount]']
    .forEach(function (n) { if (form.elements[n]) form.elements[n].addEventListener('input', recalc); });
  if ($('#full_payment')) $('#full_payment').addEventListener('change', recalc);

  // ------------------------------------------------- item pickers (jackets)
  document.querySelectorAll('.picker').forEach(function (picker) {
    var input = $('.picker-input', picker), hidden = $('input[type=hidden]', picker), list = $('.picker-list', picker);
    var timer = null;

    function load() {
      var cat = picker.dataset.category;
      var params = new URLSearchParams({
        category: cat,
        q: input.value.trim(),
        date: form.elements.wedding_date.value,
        need: cat === 'bestman' ? Math.max(1, val('bestmen_count')) : 1,
        exclude: form.dataset.billId || 0
      });
      fetch('/billing/items?' + params.toString(), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (rows) {
          list.innerHTML = '';
          if (!rows.length) {
            var none = document.createElement('li');
            none.className = 'none';
            none.textContent = 'No available items for this date';
            list.appendChild(none);
          }
          rows.forEach(function (row) {
            var li = document.createElement('li');
            li.textContent = row.code + ' \u2013 ' + row.name + ' ';
            var small = document.createElement('small');
            small.textContent = '(size ' + row.size + ', ' + row.remaining + ' left)';
            li.appendChild(small);
            li.addEventListener('mousedown', function (e) {
              e.preventDefault();
              input.value = row.code + ' \u2013 ' + row.name;
              hidden.value = row.id;
              list.hidden = true;
            });
            list.appendChild(li);
          });
          list.hidden = false;
        })
        .catch(function () { list.hidden = true; });
    }

    input.addEventListener('input', function () {
      hidden.value = '';                       // typing again clears the previous pick
      clearTimeout(timer);
      timer = setTimeout(load, 250);
    });
    input.addEventListener('focus', load);
    input.addEventListener('blur', function () { setTimeout(function () { list.hidden = true; }, 150); });
  });

  // A date or bestman-count change can free / lock items: ask the user to pick again.
  ['wedding_date', 'bestmen_count'].forEach(function (n) {
    form.elements[n].addEventListener('change', function () {
      document.querySelectorAll('.picker').forEach(function (p) {
        if ($('input[type=hidden]', p).value) { $('.picker-input', p).style.borderColor = '#f59e0b'; }
      });
    });
  });

  // ------------------------------------------------- party measurement rows
  var body = $('#members');
  var saved = window.BILL_MEMBERS || {};
  var fields = ['name', 'cap', 'jacket', 'trouser', 'shoe'];

  function remember() {
    body.querySelectorAll('input').forEach(function (inp) {
      var m = inp.name.match(/^members\[([^\]]+)\]\[(\w+)\]$/);
      if (!m) return;
      saved[m[1]] = saved[m[1]] || {};
      saved[m[1]][m[2]] = inp.value;
    });
  }

  function renderMembers() {
    remember();
    var bestmen = Math.min(20, Math.max(0, parseInt(form.elements.bestmen_count.value, 10) || 0));
    var pageboys = Math.min(20, Math.max(0, parseInt(form.elements.pageboys_count.value, 10) || 0));
    var rows = [['groom-1', 'Groom']];
    for (var i = 1; i <= bestmen; i++) rows.push(['bestman-' + i, 'Bestman ' + i]);
    for (var j = 1; j <= pageboys; j++) rows.push(['pageboy-' + j, 'Page Boy ' + j]);

    body.innerHTML = '';
    rows.forEach(function (r) {
      var tr = document.createElement('tr');
      var th = document.createElement('td');
      th.innerHTML = '<strong></strong>';
      th.firstChild.textContent = r[1];
      tr.appendChild(th);
      fields.forEach(function (f) {
        var td = document.createElement('td');
        var inp = document.createElement('input');
        inp.type = 'text';
        inp.maxLength = f === 'name' ? 100 : 30;
        inp.name = 'members[' + r[0] + '][' + f + ']';
        inp.value = (saved[r[0]] && saved[r[0]][f]) || '';
        td.appendChild(inp);
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });
  }
  form.elements.bestmen_count.addEventListener('input', renderMembers);
  form.elements.pageboys_count.addEventListener('input', renderMembers);

  // ------------------------------------------------- find existing customer
  var searchBtn = $('#customer-search-btn');
  if (searchBtn) {
    var sInput = $('#customer-search'), results = $('#customer-results');
    var run = function () {
      var q = sInput.value.trim();
      if (q.length < 2) { results.hidden = true; return; }
      fetch('/billing/customers?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (rows) {
          results.innerHTML = '';
          if (!rows.length) {
            var none = document.createElement('li');
            none.className = 'none';
            none.textContent = 'No earlier customer found';
            results.appendChild(none);
          }
          rows.forEach(function (c) {
            var li = document.createElement('li');
            li.textContent = c.customer_name + ' \u2013 ' + c.phone1;
            li.addEventListener('click', function () {
              form.elements.customer_name.value = c.customer_name;
              form.elements.customer_email.value = c.customer_email;
              form.elements.address.value = c.address;
              form.elements.phone1.value = c.phone1;
              form.elements.phone2.value = c.phone2;
              results.hidden = true;
            });
            results.appendChild(li);
          });
          results.hidden = false;
        });
    };
    searchBtn.addEventListener('click', run);
    sInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); run(); } });
  }

  var resetBtn = $('#reset-btn');
  if (resetBtn) resetBtn.addEventListener('click', function () { setTimeout(function () { saved = {}; renderMembers(); recalc(); }, 0); });

  renderMembers();
  recalc();
})();
