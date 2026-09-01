/**
 * Verificador de bordas das tabelas do report_unasus.
 *
 * Mede, no DOM, se cada divisa entre celulas tem EXATAMENTE UMA borda visivel.
 * Duas bordas na mesma divisa e' o defeito que o border-collapse esconde enquanto
 * nada rola: o navegador funde as duas e se ve uma so'. A fusao se desfaz quando
 * uma celula position:sticky se desloca, e a divisa aparece com 2px ou some.
 *
 * Uso: colar no console com a tabela do relatorio na tela. Devolve
 * `total: 0` quando esta' correto, e aponta as celulas envolvidas quando nao.
 *
 * ⚠️ Considera tambem ::before/::after, porque ja' houve versao que desenhava as
 * linhas das celulas presas num pseudo-elemento.
 */
(function () {
  var TOL = 0.7; // tolerancia geometrica em px CSS

  function alfa(cor) {
    var m = cor.match(/rgba?\(([^)]+)\)/);
    if (!m) { return 1; }
    var p = m[1].split(',').map(Number);
    return p.length > 3 ? p[3] : 1;
  }

  function aresta(el, lado) {
    var nome = 'border' + lado + 'Width', cor = 'border' + lado + 'Color', w = 0;
    [null, '::after', '::before'].forEach(function (ps) {
      var c = getComputedStyle(el, ps);
      if (ps && c.content === 'none') { return; }
      var v = parseFloat(c[nome]) || 0;
      if (v > 0 && alfa(c[cor]) > 0.05) { w = Math.max(w, v); }
    });
    return w;
  }

  function verifica(t) {
    var cels = [].slice.call(t.querySelectorAll('th,td')).map(function (el) {
      var b = el.getBoundingClientRect();
      return {el: el, b: b, R: aresta(el, 'Right'), L: aresta(el, 'Left'),
              T: aresta(el, 'Top'), B: aresta(el, 'Bottom')};
    }).filter(function (c) { return c.b.width > 0 && c.b.height > 0; });

    // indexa por aresta arredondada: sem isto sao 11 mil celulas ao quadrado
    var porLeft = new Map(), porTop = new Map();
    function pu(m, k, v) { var a = m.get(k); if (a) { a.push(v); } else { m.set(k, [v]); } }
    cels.forEach(function (c) {
      pu(porLeft, Math.round(c.b.left), c);
      pu(porTop, Math.round(c.b.top), c);
    });

    var f = {vDuas: 0, vZero: 0, hDuas: 0, hZero: 0}, ex = [];
    cels.forEach(function (A) {
      [-1, 0, 1].forEach(function (d) {
        (porLeft.get(Math.round(A.b.right) + d) || []).forEach(function (B) {
          if (A === B || Math.abs(A.b.right - B.b.left) > TOL) { return; }
          if (Math.min(A.b.bottom, B.b.bottom) - Math.max(A.b.top, B.b.top) < 1) { return; }
          var n = (A.R > 0 ? 1 : 0) + (B.L > 0 ? 1 : 0);
          if (n === 2) { f.vDuas++; if (ex.length < 5) { ex.push('vertical 2 bordas: ' + A.el.className + ' | ' + B.el.className); } }
          else if (n === 0) { f.vZero++; if (ex.length < 5) { ex.push('vertical sem borda: ' + A.el.className + ' | ' + B.el.className); } }
        });
        (porTop.get(Math.round(A.b.bottom) + d) || []).forEach(function (B) {
          if (A === B || Math.abs(A.b.bottom - B.b.top) > TOL) { return; }
          if (Math.min(A.b.right, B.b.right) - Math.max(A.b.left, B.b.left) < 1) { return; }
          var n = (A.B > 0 ? 1 : 0) + (B.T > 0 ? 1 : 0);
          if (n === 2) { f.hDuas++; if (ex.length < 5) { ex.push('horizontal 2 bordas: ' + A.el.className + ' | ' + B.el.className); } }
          else if (n === 0) { f.hZero++; if (ex.length < 5) { ex.push('horizontal sem borda: ' + A.el.className + ' | ' + B.el.className); } }
        });
      });
    });

    var larg = new Set();
    cels.forEach(function (c) { [c.R, c.L, c.T, c.B].forEach(function (w) { if (w > 0) { larg.add(+w.toFixed(3)); } }); });

    return {
      celulas: cels.length,
      largurasCSS: [].slice.call(larg).sort(function (a, b) { return a - b; }),
      falhas: f,
      total: f.vDuas + f.vZero + f.hDuas + f.hZero,
      exemplos: ex
    };
  }

  // ⚠️ Nem toda tabela de relatorio leva a classe relatorio-unasus: a do
  // modulos_concluidos nao leva. Filtrar por ela faz o verificador pular o
  // relatorio em silencio e reportar cobertura que nao existe.
  var t = [].slice.call(document.querySelectorAll('.relatorio-wrapper table'))
            .filter(function (x) { return !x.classList.contains('mapa-calor'); })[0];
  return t ? verifica(t) : {erro: 'nenhuma tabela de relatorio nesta pagina'};
})()
