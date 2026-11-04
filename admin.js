/* =========================================================
   PAINEL ADMIN — ConstrutoMAX
   Todo texto vindo do banco entra na tela via textContent
   (nunca innerHTML), então não há risco de injeção de HTML.
========================================================= */
(function () {
  'use strict';

  var CSRF = document.querySelector('meta[name="csrf-token"]').content;
  var TZ = 'America/Fortaleza';

  var QUOTE_STATUS = { novo: 'Novo', em_atendimento: 'Em atendimento', respondido: 'Respondido', fechado: 'Fechado', cancelado: 'Cancelado' };
  var QUOTE_TONE = { novo: 'new', em_atendimento: 'progress', respondido: 'progress', fechado: 'ok', cancelado: 'off' };
  var ORDER_STATUS = { aguardando_pagamento: 'Aguardando pagamento', pago: 'Pago', em_separacao: 'Em separação', enviado: 'Enviado', entregue: 'Entregue', cancelado: 'Cancelado' };
  var ORDER_TONE = { aguardando_pagamento: 'new', pago: 'progress', em_separacao: 'progress', enviado: 'progress', entregue: 'ok', cancelado: 'off' };
  var PAY = { pix: 'Pix', cartao: 'Cartão', boleto: 'Boleto', faturado: 'Faturado' };

  /* ---------- utilidades ---------- */
  function $(sel, root) { return (root || document).querySelector(sel); }

  function el(tag, props, kids) {
    var n = document.createElement(tag);
    if (props) {
      Object.keys(props).forEach(function (k) {
        var v = props[k];
        if (v === null || v === undefined || v === false) return;
        if (k === 'class') n.className = v;
        else if (k === 'text') n.textContent = v;
        else if (k.slice(0, 2) === 'on') n.addEventListener(k.slice(2), v);
        else n.setAttribute(k, v === true ? '' : v);
      });
    }
    (kids || []).forEach(function (c) {
      if (c === null || c === undefined || c === false) return;
      n.appendChild(typeof c === 'object' ? c : document.createTextNode(String(c)));
    });
    return n;
  }

  function brl(cents) {
    return (Number(cents || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function parseSql(s) { return new Date(String(s).replace(' ', 'T') + 'Z'); }

  function fmtDateTime(s) {
    var d = parseSql(s);
    return isNaN(d) ? '—' : d.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short', timeZone: TZ });
  }

  function digits(v) { return String(v || '').replace(/\D+/g, ''); }

  function fmtPhone(v) {
    var d = digits(v);
    if ((d.length === 12 || d.length === 13) && d.indexOf('55') === 0) d = d.slice(2);
    if (d.length === 11) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
    if (d.length === 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
    return d || '—';
  }

  function waUrl(v) {
    var d = digits(v);
    if (!d) return '';
    if (d.length <= 11) d = '55' + d;
    return 'https://wa.me/' + d;
  }

  function waLink(v) {
    var url = waUrl(v);
    if (!url) return '—';
    return el('a', { class: 'wa', href: url, target: '_blank', rel: 'noopener noreferrer', title: 'Abrir conversa no WhatsApp', text: fmtPhone(v) });
  }

  function debounce(fn, ms) {
    var t;
    return function () { clearTimeout(t); t = setTimeout(fn, ms); };
  }

  var toastTimer;
  function toast(msg, isError) {
    var t = $('#toast');
    t.textContent = msg;
    t.className = 'toast show' + (isError ? ' error' : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('show'); }, 4000);
  }

  /* ---------- API ---------- */
  function api(action, params, body) {
    var url = 'admin_api.php?action=' + encodeURIComponent(action);
    Object.keys(params || {}).forEach(function (k) {
      var v = params[k];
      if (v !== '' && v !== null && v !== undefined) url += '&' + k + '=' + encodeURIComponent(v);
    });
    var init = { credentials: 'same-origin', headers: { Accept: 'application/json' } };
    if (body) {
      init.method = 'POST';
      init.headers['Content-Type'] = 'application/json';
      init.headers['X-CSRF-Token'] = CSRF;
      init.body = JSON.stringify(body);
    }
    return fetch(url, init).then(function (r) {
      return r.json().then(function (j) { return { status: r.status, json: j }; }, function () {
        return { status: r.status, json: { ok: false, error: 'Resposta inválida do servidor.' } };
      });
    }).then(function (x) {
      if (x.status === 403 || x.status === 401) { window.location.replace('index.html'); }
      return x.json;
    }).catch(function () {
      return { ok: false, error: 'Sem conexão com o servidor. Verifique a internet e tente de novo.' };
    });
  }

  /* ---------- contagens do menu ---------- */
  function setBadge(sel, n) {
    var b = $(sel);
    n = Number(n || 0);
    b.textContent = n > 99 ? '99+' : String(n);
    b.hidden = n <= 0;
    b.setAttribute('aria-label', n + ' pendentes');
  }

  function refreshCounts() {
    api('counts').then(function (res) {
      if (!res.ok) return;
      setBadge('#navPedidos', res.orders_pending);
      setBadge('#navOrcamentos', res.quotes_new);
    });
  }

  /* ---------- seletor de status ---------- */
  function statusSelect(type, row, onChange) {
    var map = type === 'order' ? ORDER_STATUS : QUOTE_STATUS;
    var tones = type === 'order' ? ORDER_TONE : QUOTE_TONE;
    var noun = type === 'order' ? 'Pedido' : 'Orçamento';
    var sel = el('select', {
      class: 'status-select tone-' + (tones[row.status] || 'off'),
      'aria-label': 'Status do ' + noun.toLowerCase() + ' #' + row.id
    });
    Object.keys(map).forEach(function (k) { sel.appendChild(el('option', { value: k, text: map[k] })); });
    if (!map[row.status]) sel.insertBefore(el('option', { value: row.status, text: row.status }), sel.firstChild);
    sel.value = row.status;
    var current = row.status;

    sel.addEventListener('change', function () {
      var next = sel.value;
      sel.disabled = true;
      api('set_status', null, { type: type, id: row.id, status: next }).then(function (res) {
        sel.disabled = false;
        if (!res.ok) {
          sel.value = current;
          toast(res.error || 'Não foi possível atualizar o status.', true);
          return;
        }
        current = next;
        row.status = next;
        sel.className = 'status-select tone-' + (tones[next] || 'off');
        toast(noun + ' #' + row.id + ' agora está como "' + map[next] + '".');
        refreshCounts();
        if (onChange) onChange(next);
      });
    });
    return sel;
  }

  function pill(type, status) {
    var map = type === 'order' ? ORDER_STATUS : QUOTE_STATUS;
    var tones = type === 'order' ? ORDER_TONE : QUOTE_TONE;
    return el('span', { class: 'pill tone-' + (tones[status] || 'off'), text: map[status] || status });
  }

  /* ---------- tabela, mensagens e paginação ---------- */
  function showMessage(host, title, text, actionLabel, action) {
    var box = el('div', { class: 'state' }, [el('strong', { text: title }), text ? el('p', { text: text }) : null]);
    if (actionLabel) box.appendChild(el('button', { type: 'button', class: 'btn btn-ghost btn-sm', text: actionLabel, onclick: action }));
    host.replaceChildren(box);
  }

  function renderTable(host, cols, rows) {
    var head = el('tr', null, cols.map(function (c) { return el('th', { scope: 'col', class: c.cls || '', text: c.label }); }));
    var body = rows.map(function (r) {
      return el('tr', null, cols.map(function (c) {
        return el('td', { class: c.cls || '', 'data-label': c.label }, [].concat(c.render(r)));
      }));
    });
    host.replaceChildren(el('div', { class: 'tbl-wrap' }, [
      el('table', { class: 'tbl' }, [el('thead', null, [head]), el('tbody', null, body)])
    ]));
  }

  function renderPager(host, st, res, go) {
    var pages = Math.max(1, Math.ceil(res.total / res.page_size));
    var from = (res.page - 1) * res.page_size + 1;
    var to = from + res.rows.length - 1;
    host.replaceChildren(
      el('span', { text: from + '–' + to + ' de ' + res.total }),
      el('div', { class: 'pager-btns' }, [
        el('button', { type: 'button', class: 'btn btn-ghost btn-sm', text: 'Anterior', disabled: res.page <= 1, onclick: function () { st.page = res.page - 1; go(); } }),
        el('span', { text: 'Página ' + res.page + ' de ' + pages }),
        el('button', { type: 'button', class: 'btn btn-ghost btn-sm', text: 'Próxima', disabled: res.page >= pages, onclick: function () { st.page = res.page + 1; go(); } })
      ])
    );
  }

  /* ---------- gaveta de detalhes ---------- */
  var drawer = $('#drawer');
  var drawerToken = 0;

  function openDrawer(title, content) {
    $('#drawerTitle').textContent = title;
    $('#drawerBody').replaceChildren(content);
    if (!drawer.open) drawer.showModal();
  }

  drawer.addEventListener('click', function (e) { if (e.target === drawer) drawer.close(); });
  $('#drawerClose').addEventListener('click', function () { drawer.close(); });
  drawer.addEventListener('close', function () { drawerToken++; });

  function kv(pairs) {
    var dl = el('dl', { class: 'kv' });
    pairs.forEach(function (p) {
      dl.appendChild(el('dt', { text: p[0] }));
      dl.appendChild(el('dd', null, [].concat(p[1])));
    });
    return dl;
  }

  function section(title, content) {
    return el('section', { class: 'd-section' }, [el('h3', { text: title }), content]);
  }

  function waButton(phone) {
    var url = waUrl(phone);
    if (!url) return null;
    return el('a', { class: 'btn btn-primary btn-sm', href: url, target: '_blank', rel: 'noopener noreferrer', text: 'Chamar no WhatsApp' });
  }

  function openOrder(id) {
    var my = ++drawerToken;
    openDrawer('Pedido #' + id, el('p', { class: 'state state-small', text: 'Carregando…' }));
    api('order', { id: id }).then(function (res) {
      if (my !== drawerToken) return;
      if (!res.ok) {
        openDrawer('Pedido #' + id, el('p', { class: 'state state-small', text: res.error || 'Não foi possível carregar o pedido.' }));
        return;
      }
      var o = res.order;
      var items = res.items || [];
      var wrap = el('div');

      wrap.appendChild(section('Status', statusSelect('order', o, reloadCurrent)));

      wrap.appendChild(section('Cliente', el('div', null, [
        kv([
          ['Nome', o.nome],
          ['WhatsApp', fmtPhone(o.whatsapp)],
          ['Conta no site', o.email || 'Sem conta (visitante)'],
          ['Pagamento', PAY[o.forma_pagamento] || o.forma_pagamento],
          ['Recebido em', fmtDateTime(o.created_at)]
        ]),
        el('p', { style: 'margin-top:14px' }, [waButton(o.whatsapp)])
      ])));

      if (o.mensagem) wrap.appendChild(section('Observações do cliente', el('pre', { class: 'msg', text: o.mensagem })));

      var itemsBox;
      if (items.length === 0) {
        itemsBox = el('p', { class: 'sub', text: 'Nenhum item registrado neste pedido.' });
      } else {
        itemsBox = el('div', { class: 'items-wrap' }, [el('table', { class: 'items' }, [
          el('thead', null, [el('tr', null, [
            el('th', { text: 'Produto' }), el('th', { class: 'num', text: 'Qtd.' }),
            el('th', { class: 'num', text: 'Unitário' }), el('th', { class: 'num', text: 'Subtotal' })
          ])]),
          el('tbody', null, items.map(function (it) {
            return el('tr', null, [
              el('td', null, [it.nome]),
              el('td', { class: 'num', text: it.quantidade + ' ' + it.unidade }),
              el('td', { class: 'num', text: brl(it.preco_cents) }),
              el('td', { class: 'num', text: brl(it.preco_cents * it.quantidade) })
            ]);
          }))
        ])]);
      }
      wrap.appendChild(section('Itens do pedido', itemsBox));
      wrap.appendChild(el('div', { class: 'total-row' }, [el('span', { text: 'Total do pedido' }), el('strong', { text: brl(o.total_cents) })]));

      openDrawer('Pedido #' + o.id, wrap);
    });
  }

  function openQuote(id) {
    var my = ++drawerToken;
    openDrawer('Orçamento #' + id, el('p', { class: 'state state-small', text: 'Carregando…' }));
    api('quote', { id: id }).then(function (res) {
      if (my !== drawerToken) return;
      if (!res.ok) {
        openDrawer('Orçamento #' + id, el('p', { class: 'state state-small', text: res.error || 'Não foi possível carregar o orçamento.' }));
        return;
      }
      var q = res.quote;
      var wrap = el('div');
      wrap.appendChild(section('Status', statusSelect('quote', q, reloadCurrent)));
      wrap.appendChild(section('Cliente', el('div', null, [
        kv([
          ['Nome', q.nome],
          ['WhatsApp', fmtPhone(q.whatsapp)],
          ['Conta no site', q.email || 'Sem conta (visitante)'],
          ['Recebido em', fmtDateTime(q.created_at)]
        ]),
        el('p', { style: 'margin-top:14px' }, [waButton(q.whatsapp)])
      ])));
      wrap.appendChild(section('O que o cliente pediu', el('pre', { class: 'msg', text: q.mensagem })));
      openDrawer('Orçamento #' + q.id, wrap);
    });
  }

  /* ---------- listas (pedidos, orçamentos, clientes) ---------- */
  function idCell(type, r) {
    var open = type === 'order' ? openOrder : openQuote;
    return el('div', { class: 'stack' }, [
      el('button', { type: 'button', class: 'link-btn', text: '#' + r.id, 'aria-label': 'Ver detalhes de #' + r.id, onclick: function () { open(r.id); } }),
      el('small', { text: fmtDateTime(r.created_at) })
    ]);
  }

  function personCell(r) {
    return el('div', { class: 'stack' }, [el('strong', { text: r.nome }), el('small', { text: r.email || 'Sem conta' })]);
  }

  var LISTS = {
    pedidos: {
      action: 'orders', exportType: 'orders',
      empty: ['Nenhum pedido ainda', 'Quando um cliente enviar um pedido pelo site, ele aparece aqui.'],
      emptyFiltered: ['Nenhum pedido encontrado', 'Tente outro termo de busca ou outro status.'],
      cols: function () {
        return [
          { label: 'Pedido', render: function (r) { return idCell('order', r); } },
          { label: 'Cliente', render: personCell },
          { label: 'WhatsApp', render: function (r) { return waLink(r.whatsapp); } },
          { label: 'Pagamento', render: function (r) { return PAY[r.forma_pagamento] || r.forma_pagamento; } },
          { label: 'Total', cls: 'num money', render: function (r) { return brl(r.total_cents); } },
          { label: 'Status', render: function (r) { return statusSelect('order', r); } }
        ];
      }
    },
    orcamentos: {
      action: 'quotes', exportType: 'quotes',
      empty: ['Nenhum orçamento ainda', 'Os pedidos de orçamento enviados pelo site aparecem aqui.'],
      emptyFiltered: ['Nenhum orçamento encontrado', 'Tente outro termo de busca ou outro status.'],
      cols: function () {
        return [
          { label: 'Orçamento', render: function (r) { return idCell('quote', r); } },
          { label: 'Cliente', render: personCell },
          { label: 'WhatsApp', render: function (r) { return waLink(r.whatsapp); } },
          { label: 'Pedido do cliente', render: function (r) { return el('span', { class: 'clamp', text: r.mensagem }); } },
          { label: 'Status', render: function (r) { return statusSelect('quote', r); } }
        ];
      }
    },
    clientes: {
      action: 'users', exportType: 'users',
      empty: ['Nenhum cliente cadastrado', 'As contas criadas no site aparecem aqui.'],
      emptyFiltered: ['Nenhum cliente encontrado', 'Tente buscar por outro nome, e-mail ou WhatsApp.'],
      cols: function () {
        return [
          { label: 'Cliente', render: function (r) {
            return el('span', null, [el('strong', { text: r.nome }), r.is_admin ? el('span', { class: 'tag-admin', text: 'Admin' }) : null]);
          } },
          { label: 'E-mail', render: function (r) { return el('span', { class: 'sub', text: r.email }); } },
          { label: 'WhatsApp', render: function (r) { return waLink(r.whatsapp); } },
          { label: 'Cadastro', render: function (r) { return fmtDateTime(r.created_at); } },
          { label: 'Pedidos', cls: 'num', render: function (r) { return String(r.pedidos); } },
          { label: 'Orçamentos', cls: 'num', render: function (r) { return String(r.orcamentos); } }
        ];
      }
    }
  };

  var state = {
    pedidos: { q: '', status: '', page: 1, req: 0 },
    orcamentos: { q: '', status: '', page: 1, req: 0 },
    clientes: { q: '', status: '', page: 1, req: 0 }
  };

  function loadList(key) {
    var cfg = LISTS[key], st = state[key];
    var host = $('#' + key + 'List'), pager = $('#' + key + 'Pager');
    var my = ++st.req;
    if (!host.firstChild) showMessage(host, 'Carregando…');

    api(cfg.action, { q: st.q, status: st.status, page: st.page }).then(function (res) {
      if (my !== st.req) return; // resposta antiga: já existe uma busca mais nova
      if (!res.ok) {
        showMessage(host, 'Não foi possível carregar', res.error || 'Tente novamente.', 'Tentar de novo', function () { loadList(key); });
        pager.replaceChildren();
        return;
      }
      if (res.rows.length === 0 && res.total > 0 && st.page > 1) {
        st.page = Math.max(1, Math.ceil(res.total / res.page_size));
        loadList(key);
        return;
      }
      if (res.rows.length === 0) {
        var m = (st.q || st.status) ? cfg.emptyFiltered : cfg.empty;
        showMessage(host, m[0], m[1]);
        pager.replaceChildren();
        return;
      }
      renderTable(host, cfg.cols(), res.rows);
      renderPager(pager, st, res, function () { loadList(key); });
    });
  }

  function setupList(key, statusMap) {
    var st = state[key];
    var search = $('#' + key + 'Search');
    var status = $('#' + key + 'Status');

    search.addEventListener('input', debounce(function () {
      st.q = search.value.trim();
      st.page = 1;
      loadList(key);
    }, 300));

    if (status && statusMap) {
      status.appendChild(el('option', { value: '', text: 'Todos os status' }));
      Object.keys(statusMap).forEach(function (k) { status.appendChild(el('option', { value: k, text: statusMap[k] })); });
      status.addEventListener('change', function () {
        st.status = status.value;
        st.page = 1;
        loadList(key);
      });
    }

    $('#' + key + 'Export').addEventListener('click', function () {
      var url = 'admin_api.php?action=export&type=' + LISTS[key].exportType +
        '&q=' + encodeURIComponent(st.q) + '&status=' + encodeURIComponent(st.status);
      window.location.href = url;
    });
  }

  setupList('pedidos', ORDER_STATUS);
  setupList('orcamentos', QUOTE_STATUS);
  setupList('clientes', null);

  /* ---------- visão geral ---------- */
  function renderChart(host, days) {
    var NS = 'http://www.w3.org/2000/svg';
    function s(tag, attrs, kids) {
      var n = document.createElementNS(NS, tag);
      Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
      (kids || []).forEach(function (c) { n.appendChild(c); });
      return n;
    }
    var W = 640, H = 240, L = 34, R = 6, T = 12, B = 30;
    var max = 1;
    days.forEach(function (d) { max = Math.max(max, d.orders, d.quotes); });
    var yMax = Math.max(4, Math.ceil(max / 4) * 4);
    var plotH = H - T - B;
    var slot = (W - L - R) / days.length;
    var bw = Math.min(13, slot * 0.32);
    var total = 0;

    var svg = s('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': 'Gráfico de pedidos e orçamentos por dia nos últimos 14 dias' });

    for (var i = 0; i <= 4; i++) {
      var y = T + plotH * (1 - i / 4);
      svg.appendChild(s('line', { x1: L, x2: W - R, y1: y, y2: y, class: 'grid' }));
      var t = s('text', { x: L - 8, y: y + 4, 'text-anchor': 'end' });
      t.textContent = String(yMax / 4 * i);
      svg.appendChild(t);
    }

    days.forEach(function (d, idx) {
      total += d.orders + d.quotes;
      var cx = L + slot * (idx + 0.5);
      var g = s('g');
      var tip = s('title');
      var parts = d.date.split('-');
      var label = parts[2] + '/' + parts[1];
      tip.textContent = label + ': ' + d.orders + (d.orders === 1 ? ' pedido' : ' pedidos') + ', ' + d.quotes + (d.quotes === 1 ? ' orçamento' : ' orçamentos');
      g.appendChild(tip);
      [['orders', -bw - 1], ['quotes', 1]].forEach(function (b) {
        var v = d[b[0]];
        var h = v > 0 ? Math.max(3, plotH * (v / yMax)) : 0;
        g.appendChild(s('rect', { x: cx + b[1], y: T + plotH - h, width: bw, height: h, rx: 1.5, class: 'bar-' + b[0] }));
      });
      // área de passagem do mouse para o tooltip
      g.appendChild(s('rect', { x: cx - slot / 2, y: T, width: slot, height: plotH, fill: 'transparent' }));
      var lx = s('text', { x: cx, y: H - 10, 'text-anchor': 'middle' });
      lx.textContent = label;
      g.appendChild(lx);
      svg.appendChild(g);
    });

    host.replaceChildren(svg);
    if (total === 0) host.appendChild(el('p', { class: 'chart-note', text: 'Ainda não houve pedidos nem orçamentos nesse período.' }));
  }

  function renderMini(host, rows, type) {
    if (!rows.length) {
      host.replaceChildren(el('li', { class: 'state state-small', text: type === 'order' ? 'Nenhum pedido recebido ainda.' : 'Nenhum orçamento recebido ainda.' }));
      return;
    }
    host.replaceChildren.apply(host, rows.map(function (r) {
      var open = type === 'order' ? openOrder : openQuote;
      var side = [pill(type, r.status)];
      if (type === 'order') side.unshift(el('b', { text: brl(r.total_cents) }));
      return el('li', null, [el('button', { type: 'button', class: 'mini-item', onclick: function () { open(r.id); } }, [
        el('span', { class: 'mini-main' }, [el('strong', { text: '#' + r.id + ' ' + r.nome }), el('small', { text: fmtDateTime(r.created_at) })]),
        el('span', { class: 'mini-side' }, side)
      ])]);
    }));
  }

  function loadOverview() {
    var err = $('#overviewError');
    api('stats').then(function (res) {
      if (!res.ok) {
        err.hidden = false;
        showMessage(err, 'Não foi possível carregar o resumo', res.error || 'Tente novamente.', 'Tentar de novo', loadOverview);
        return;
      }
      err.hidden = true;
      $('#kpiSales').textContent = brl(res.sales_cents);
      $('#kpiSalesSub').textContent = res.sales_count > 0
        ? 'Ticket médio ' + brl(Math.round(res.sales_cents / res.sales_count))
        : 'Nenhuma venda confirmada ainda';
      $('#kpiOrders').textContent = String(res.orders_total);
      $('#kpiOrdersSub').textContent = res.orders_pending + ' aguardando pagamento';
      $('#kpiQuotes').textContent = String(res.quotes_total);
      $('#kpiQuotesSub').textContent = res.quotes_new + (res.quotes_new === 1 ? ' novo para responder' : ' novos para responder');
      $('#kpiUsers').textContent = String(res.users_total);
      $('#kpiUsersSub').textContent = '+' + res.users_week + ' nos últimos 7 dias';
      $('#updatedAt').textContent = 'Atualizado às ' + new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', timeZone: TZ });

      renderChart($('#chartHost'), res.days);
      renderMini($('#recentOrders'), res.recent_orders, 'order');
      renderMini($('#recentQuotes'), res.recent_quotes, 'quote');
      setBadge('#navPedidos', res.orders_pending);
      setBadge('#navOrcamentos', res.quotes_new);
    });
  }

  /* ---------- faturamento ---------- */
  var REV_PERIOD_LABEL = { '7d': 'nos últimos 7 dias', '30d': 'nos últimos 30 dias', '90d': 'nos últimos 90 dias', month: 'neste mês', year: 'neste ano' };
  var MONTHS = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
  var revPeriod = $('#faturamentoPeriod');
  var revReq = 0;

  function compactBrl(cents) {
    return 'R$ ' + (cents / 100).toLocaleString('pt-BR', { notation: 'compact', maximumFractionDigits: 1 });
  }

  function niceMax(cents) {
    var v = cents / 100;
    if (v <= 0) return 10000; // R$ 100
    var pow = Math.pow(10, Math.floor(Math.log10(v)));
    var steps = [1, 2, 4, 8, 10];
    for (var i = 0; i < steps.length; i++) {
      if (steps[i] * pow >= v) return steps[i] * pow * 100;
    }
    return 10 * pow * 100;
  }

  function renderRevenueChart(host, buckets, group) {
    var NS = 'http://www.w3.org/2000/svg';
    function s(tag, attrs, kids) {
      var n = document.createElementNS(NS, tag);
      Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
      (kids || []).forEach(function (c) { n.appendChild(c); });
      return n;
    }
    var W = 640, H = 240, L = 62, R = 6, T = 12, B = 30;
    var max = 0, total = 0;
    buckets.forEach(function (b) { max = Math.max(max, b.cents); total += b.cents; });
    var yMax = niceMax(max);
    var plotH = H - T - B;
    var slot = (W - L - R) / buckets.length;
    var bw = Math.min(24, slot * 0.7);
    var every = Math.max(1, Math.ceil(buckets.length / 10));

    var svg = s('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': 'Gráfico de faturamento por ' + (group === 'month' ? 'mês' : 'dia') });

    for (var i = 0; i <= 4; i++) {
      var y = T + plotH * (1 - i / 4);
      svg.appendChild(s('line', { x1: L, x2: W - R, y1: y, y2: y, class: 'grid' }));
      var t = s('text', { x: L - 8, y: y + 4, 'text-anchor': 'end' });
      t.textContent = i === 0 ? 'R$ 0' : compactBrl(yMax / 4 * i);
      svg.appendChild(t);
    }

    buckets.forEach(function (b, idx) {
      var parts = b.key.split('-');
      var label = group === 'month' ? MONTHS[Number(parts[1]) - 1] : parts[2] + '/' + parts[1];
      var cx = L + slot * (idx + 0.5);
      var g = s('g');
      var tip = s('title');
      tip.textContent = label + ': ' + brl(b.cents) + ' (' + b.orders + (b.orders === 1 ? ' pedido' : ' pedidos') + ')';
      g.appendChild(tip);
      var h = b.cents > 0 ? Math.max(3, plotH * (b.cents / yMax)) : 0;
      g.appendChild(s('rect', { x: cx - bw / 2, y: T + plotH - h, width: bw, height: h, rx: 1.5, class: 'bar-revenue' }));
      g.appendChild(s('rect', { x: cx - slot / 2, y: T, width: slot, height: plotH, fill: 'transparent' }));
      if (idx % every === 0) {
        var lx = s('text', { x: cx, y: H - 10, 'text-anchor': 'middle' });
        lx.textContent = label;
        g.appendChild(lx);
      }
      svg.appendChild(g);
    });

    host.replaceChildren(svg);
    if (total === 0) host.appendChild(el('p', { class: 'chart-note', text: 'Ainda não houve vendas confirmadas nesse período.' }));
  }

  function loadRevenue() {
    var err = $('#faturamentoError');
    var my = ++revReq;
    api('revenue', { period: revPeriod.value }).then(function (res) {
      if (my !== revReq) return; // resposta antiga
      if (!res.ok) {
        err.hidden = false;
        showMessage(err, 'Não foi possível carregar o faturamento', res.error || 'Tente novamente.', 'Tentar de novo', loadRevenue);
        return;
      }
      err.hidden = true;
      var label = REV_PERIOD_LABEL[res.period] || '';

      $('#fatKpiRevenue').textContent = brl(res.revenue_cents);
      $('#fatKpiRevenueSub').textContent = res.orders_count > 0 ? 'Vendas confirmadas ' + label : 'Nenhuma venda confirmada ' + label;
      $('#fatKpiOrders').textContent = String(res.orders_count);
      $('#fatKpiOrdersSub').textContent = res.canceled_count + (res.canceled_count === 1 ? ' pedido cancelado' : ' pedidos cancelados');
      $('#fatKpiAvg').textContent = brl(res.avg_cents);
      $('#fatKpiAvgSub').textContent = res.orders_count > 0 ? 'Por pedido confirmado' : '';
      $('#fatKpiPending').textContent = brl(res.pending_cents);
      $('#fatKpiPendingSub').textContent = res.pending_count + (res.pending_count === 1 ? ' pedido aguardando pagamento' : ' pedidos aguardando pagamento');

      $('#fatChartTitle').textContent = res.group === 'month' ? 'Faturamento por mês' : 'Faturamento por dia';
      renderRevenueChart($('#fatChartHost'), res.buckets, res.group);

      var payHost = $('#fatPayList');
      if (!res.by_payment.length) {
        showMessage(payHost, 'Sem vendas confirmadas', 'Quando houver pedidos pagos nesse período, o resumo por forma de pagamento aparece aqui.');
        return;
      }
      renderTable(payHost, [
        { label: 'Forma de pagamento', render: function (r) { return PAY[r.forma_pagamento] || r.forma_pagamento; } },
        { label: 'Pedidos', cls: 'num', render: function (r) { return String(r.orders); } },
        { label: 'Total', cls: 'num money', render: function (r) { return brl(r.total_cents); } },
        { label: 'Participação', cls: 'num', render: function (r) {
          var pct = res.revenue_cents > 0 ? r.total_cents / res.revenue_cents * 100 : 0;
          return pct.toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%';
        } }
      ], res.by_payment);
    });
  }

  revPeriod.addEventListener('change', loadRevenue);

  /* ---------- rotas ---------- */
  var ROUTES = {
    'visao-geral': { title: 'Visão geral', load: loadOverview },
    pedidos: { title: 'Pedidos', load: function () { loadList('pedidos'); } },
    orcamentos: { title: 'Orçamentos', load: function () { loadList('orcamentos'); } },
    clientes: { title: 'Clientes', load: function () { loadList('clientes'); } },
    faturamento: { title: 'Faturamento', load: loadRevenue }
  };
  var currentKey = null;

  function route(isFirst) {
    var key = window.location.hash.replace(/^#\/?/, '') || 'visao-geral';
    if (!ROUTES[key]) key = 'visao-geral';
    currentKey = key;

    Object.keys(ROUTES).forEach(function (k) { $('#view-' + k).hidden = k !== key; });
    document.querySelectorAll('.nav a').forEach(function (a) {
      if (a.dataset.route === key) a.setAttribute('aria-current', 'page');
      else a.removeAttribute('aria-current');
    });
    $('#pageTitle').textContent = ROUTES[key].title;
    document.title = ROUTES[key].title + ' — Painel admin ConstrutoMAX';
    if (!isFirst) $('#pageTitle').focus();
    window.scrollTo(0, 0);
    ROUTES[key].load();
  }

  function reloadCurrent() {
    if (currentKey && ROUTES[currentKey]) ROUTES[currentKey].load();
  }

  window.addEventListener('hashchange', function () { route(false); });
  $('#refreshBtn').addEventListener('click', function () { reloadCurrent(); refreshCounts(); });

  /* ---------- sair ---------- */
  $('#logoutBtn').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    fetch('logout.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: '{}' })
      .catch(function () {})
      .then(function () { window.location.href = 'index.html'; });
  });

  route(true);
  refreshCounts();
})();