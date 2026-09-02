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

    // ⚠️ EXCECAO DELIBERADA: a divisa entre duas celulas VAZIAS nao leva linha.
    //
    // As celulas vazias existem so' para carregar a divisa HORIZONTAL das linhas
    // irregulares (ver `celula_vazia` no styles.css). Elas nao representam coluna de
    // dado nenhuma, e a area vazia da tabela nunca teve grade -- desenhar a vertical ali
    // encheria de linhas um espaco que e' branco de proposito.
    //
    // A excecao e' so' entre DUAS vazias: a divisa entre uma celula de dado e a vazia ao
    // lado continua sendo cobrada, e quem a desenha e' a celula de dado.
    function vazia(c) { return c.el.classList.contains('celula_vazia'); }
    cels.forEach(function (A) {
      [-1, 0, 1].forEach(function (d) {
        (porLeft.get(Math.round(A.b.right) + d) || []).forEach(function (B) {
          if (A === B || Math.abs(A.b.right - B.b.left) > TOL) { return; }
          if (Math.min(A.b.bottom, B.b.bottom) - Math.max(A.b.top, B.b.top) < 1) { return; }
          if (vazia(A) && vazia(B)) { return; }
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

    // ⚠️ ARESTA SEM VIZINHA -- o ponto cego que este verificador teve.
    //
    // O laco acima so' examina PARES de celulas adjacentes. Numa tabela de linhas
    // irregulares (as listas de atividades pendentes: cada estudante tem tantas
    // celulas quantas forem as pendencias dele) a celula que sobra nao tem ninguem
    // acima -- nao forma par, nenhuma divisa e' examinada, e a falta da linha superior
    // passa batido. Foi assim que a tela mostrou o defeito com o verificador dando
    // `total: 0`.
    //
    // Regra: onde NAO ha celula vizinha, a propria celula tem de desenhar a aresta.
    // A unica excecao e' a borda esquerda da tabela, que pertence ao .relatorio-wrapper.
    var porRight = new Map(), porBottom = new Map();
    cels.forEach(function (c) {
      pu(porRight, Math.round(c.b.right), c);
      pu(porBottom, Math.round(c.b.bottom), c);
    });

    var esquerdaDaTabela = Math.round(t.getBoundingClientRect().left);

    function temVizinha(A, indice, coord, eixoVertical) {
      var achou = false;
      [-1, 0, 1].forEach(function (d) {
        (indice.get(Math.round(coord) + d) || []).forEach(function (B) {
          if (A === B) { return; }
          var sobrepoe = eixoVertical
            ? Math.min(A.b.right, B.b.right) - Math.max(A.b.left, B.b.left) >= 1
            : Math.min(A.b.bottom, B.b.bottom) - Math.max(A.b.top, B.b.top) >= 1;
          if (sobrepoe) { achou = true; }
        });
      });
      return achou;
    }

    f.orfaTopo = 0; f.orfaBaixo = 0; f.orfaEsquerda = 0; f.orfaDireita = 0;

    cels.forEach(function (A) {
      // A vazia nao precisa de teto: ela nao e' coluna de dado, existe so' para carregar
      // a linha de BAIXO. Exigir teto dela seria pedir divisa onde as duas linhas
      // vizinhas ja' terminaram -- que e' justamente a linha sobrando que nao se quer.
      if (!temVizinha(A, porBottom, A.b.top, true) && A.T === 0 && !vazia(A)) {
        f.orfaTopo++;
        if (ex.length < 5) { ex.push('sem linha em cima e sem celula acima: ' + A.el.className); }
      }
      if (!temVizinha(A, porTop, A.b.bottom, true) && A.B === 0) {
        f.orfaBaixo++;
        if (ex.length < 5) { ex.push('sem linha embaixo e sem celula abaixo: ' + A.el.className); }
      }
      if (!temVizinha(A, porRight, A.b.left, false) && A.L === 0 &&
          Math.abs(A.b.left - esquerdaDaTabela) > 2) {
        f.orfaEsquerda++;
        if (ex.length < 5) { ex.push('sem linha a esquerda e sem celula ao lado: ' + A.el.className); }
      }
      // A vazia no fim da linha nao fecha a direita pelo mesmo motivo: ali a tabela ja'
      // era aberta antes, quando a linha simplesmente terminava mais cedo.
      if (!temVizinha(A, porLeft, A.b.right, false) && A.R === 0 && !vazia(A)) {
        f.orfaDireita++;
        if (ex.length < 5) { ex.push('sem linha a direita e sem celula ao lado: ' + A.el.className); }
      }
    });

    var larg = new Set();
    cels.forEach(function (c) { [c.R, c.L, c.T, c.B].forEach(function (w) { if (w > 0) { larg.add(+w.toFixed(3)); } }); });

    return {
      celulas: cels.length,
      largurasCSS: [].slice.call(larg).sort(function (a, b) { return a - b; }),
      falhas: f,
      total: f.vDuas + f.vZero + f.hDuas + f.hZero +
             f.orfaTopo + f.orfaBaixo + f.orfaEsquerda + f.orfaDireita,
      exemplos: ex
    };
  }

  // ⚠️ Selecionar pelo WRAPPER, e nao pela classe relatorio-unasus na tabela.
  // Houve o tempo em que a tabela do modulos_concluidos nao levava a classe (o
  // renderer so' a acrescentava no ramo de cabecalho duplo), e filtrar por ela
  // fazia o verificador pular o relatorio em silencio, reportando uma cobertura
  // que nao existia. O renderer foi corrigido, mas o wrapper continua sendo o
  // ponto de entrada seguro: ele existe nos quatro caminhos de renderizacao.
  var t = [].slice.call(document.querySelectorAll('.relatorio-wrapper table'))
            .filter(function (x) { return !x.classList.contains('mapa-calor'); })[0];
  if (!t) { return {erro: 'nenhuma tabela de relatorio nesta pagina'}; }

  // ⚠️ MEDIR COM A TABELA NA ORIGEM.
  //
  // Este verificador acha as vizinhas pela geometria, e as celulas presas (a coluna do
  // estudante, o cabecalho) SE DESLOCAM quando a tabela rola: a coluna congelada passa
  // a coincidir com colunas distantes, e deixa um vao onde estava. O verificador entao
  // enxerga pares que nao existem e vizinhas que sumiram -- deu 57 "duas bordas" e 142
  // "sem vizinha a esquerda" numa tabela que, parada, esta' correta.
  var wrapper = t.closest('.relatorio-wrapper');
  var scroll0 = {top: wrapper.scrollTop, left: wrapper.scrollLeft};
  wrapper.scrollTop = 0;
  wrapper.scrollLeft = 0;
  var r = verifica(t);
  wrapper.scrollTop = scroll0.top;
  wrapper.scrollLeft = scroll0.left;
  return r;
})()
