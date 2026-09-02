/**
 * Verificador de TRAVA das tabelas de relatorio (report_unasus).
 *
 * Companheiro do verifica-bordas.js, que mede outra coisa: aquele conta bordas por
 * divisa, este confere se o cabecalho fica preso no topo e a coluna do estudante presa
 * na lateral quando a tabela rola. Uma tabela pode passar num e falhar no outro.
 *
 * Como usar: colar no console com o relatorio aberto em modo tabela. Criterio de
 * aprovacao: `conclusao: 'aprovado'` -- e nao `falhas: []`, ver abaixo.
 *
 * ⚠️ Por que MEDIR o deslocamento em vez de conferir o CSS: `position: sticky` falha
 * em silencio por motivos que nao aparecem na folha de estilo -- um ancestral com
 * `overflow` diferente de visible, a caixa de rolagem errada, um `top` que nunca foi
 * declarado. Ler getComputedStyle diria "sticky" nos quatro casos. Aqui a tabela e'
 * rolada de verdade e se compara a posicao antes e depois.
 */
(function () {
  // O wrapper e' a caixa de rolagem, e existe nos quatro caminhos de renderizacao --
  // ver a nota no verifica-bordas.js sobre nao filtrar pela classe da tabela.
  var wrapper = document.querySelector('.relatorio-wrapper');
  if (!wrapper) { return {erro: 'nenhuma tabela de relatorio nesta pagina'}; }

  var tabela = wrapper.querySelector('table');
  if (!tabela) { return {erro: 'wrapper sem tabela'}; }

  // Cabecalho: ou as linhas do <thead>, ou as marcadas com .cabecalho no <tbody>
  // (o table_tutores monta um cabecalho de duas linhas, que o html_table do Moodle
  // nao sabe por no thead). Sem a segunda forma, o verificador aprovaria por nao
  // encontrar nada que conferir.
  var linhasCab = [].slice.call(tabela.querySelectorAll('thead tr, tr.cabecalho'));
  var celulaTopo = linhasCab.length ? linhasCab[0].querySelector('th, td') : null;
  var celulaEsq = tabela.querySelector('th.estudante_header, td.blank');
  var celulaCorpo = tabela.querySelector('tbody th.estudante, tbody .primeira_coluna');

  function topoRelativo(el) {
    return el.getBoundingClientRect().top - wrapper.getBoundingClientRect().top;
  }
  function esqRelativa(el) {
    return el.getBoundingClientRect().left - wrapper.getBoundingClientRect().left;
  }

  var estado0 = {scrollTop: wrapper.scrollTop, scrollLeft: wrapper.scrollLeft};
  var estilo0 = {maxWidth: wrapper.style.maxWidth, maxHeight: wrapper.style.maxHeight};
  var falhas = [];
  var medidas = {};

  // ⚠️ APERTAR A CAIXA quando a tabela cabe na tela.
  //
  // Sem rolagem no eixo nao ha o que verificar nele, e o relatorio saia como "nao
  // exercitado" -- foi assim que os relatorios de TCC passaram uma varredura inteira
  // sem serem postos a' prova, e um defeito real da coluna congelada do
  // tcc_consolidado so' apareceu quando alguem abriu a pagina numa janela mais
  // estreita. Estreitar o wrapper por um instante poe qualquer tabela a' prova, com
  // qualquer volume de dado. O tamanho original e' devolvido ao fim.
  //
  // ⚠️ E o aperto tem PISO: a caixa nunca pode ficar mais estreita que a coluna
  // congelada. Uma celula sticky nao se prende dentro de um scrollport menor que ela
  // mesma -- o navegador a deixa rolar, senao o resto da tabela ficaria inalcancavel.
  // Apertando sem olhar isso, o verificador reprova uma tela correta: no
  // tcc_entrega_atividades a coluna ficou parada com a caixa em 300px e "escorregou"
  // 65px com a caixa em 200px, sendo a coluna de 250px.
  var larguraPresa = celulaCorpo ? celulaCorpo.getBoundingClientRect().width : 0;
  var pisoCaixa = Math.ceil(larguraPresa + 100);

  var apertou = {vertical: false, horizontal: false};
  if (wrapper.scrollWidth - wrapper.clientWidth <= 20) {
    var alvo = Math.max(pisoCaixa, Math.round(wrapper.clientWidth / 2));
    if (alvo < wrapper.clientWidth) {
      wrapper.style.maxWidth = alvo + 'px';
      apertou.horizontal = true;
    }
  }
  if (wrapper.scrollHeight - wrapper.clientHeight <= 20) {
    wrapper.style.maxHeight = '150px';
    apertou.vertical = true;
  }

  var rolaV = wrapper.scrollHeight - wrapper.clientHeight;
  var rolaH = wrapper.scrollWidth - wrapper.clientWidth;

  if (celulaTopo && rolaV > 20) {
    var antesV = topoRelativo(celulaTopo);
    wrapper.scrollTop = Math.min(rolaV, 400);
    var depoisV = topoRelativo(celulaTopo);
    medidas.cabecalho = {antes: Math.round(antesV), depois: Math.round(depoisV)};
    // Tolerancia de 2px: a borda de 1px e o arredondamento subpixel do navegador.
    if (Math.abs(depoisV - antesV) > 2) {
      falhas.push('cabecalho nao fica preso no topo (saiu ' +
                  Math.round(depoisV - antesV) + 'px ao rolar)');
    }
    wrapper.scrollTop = estado0.scrollTop;
  } else if (!celulaTopo) {
    falhas.push('nenhuma linha de cabecalho encontrada (nem thead, nem .cabecalho)');
  } else {
    medidas.cabecalho = 'tabela nao rola na vertical nem apertada';
  }

  // A mesma condicao vale para a janela do proprio usuario: numa caixa mais estreita
  // que a coluna congelada nao ha trava lateral possivel, e cobra-la seria cobrar do
  // navegador o que ele nao faz. Nesse caso o eixo horizontal fica sem conferencia, e
  // isso e' dito -- em vez de virar reprovacao.
  var cabeAColuna = wrapper.clientWidth >= larguraPresa + 40;

  if (rolaH > 20 && !cabeAColuna) {
    medidas.tituloDaColuna = 'caixa (' + Math.round(wrapper.clientWidth) +
      'px) mais estreita que a coluna congelada (' + Math.round(larguraPresa) +
      'px): nao ha trava lateral possivel';
  } else if (rolaH > 20) {
    if (celulaEsq) {
      var antesH = esqRelativa(celulaEsq);
      wrapper.scrollLeft = Math.min(rolaH, 400);
      var depoisH = esqRelativa(celulaEsq);
      medidas.tituloDaColuna = {antes: Math.round(antesH), depois: Math.round(depoisH)};
      if (Math.abs(depoisH - antesH) > 2) {
        falhas.push('titulo da coluna do estudante nao fica preso na lateral (saiu ' +
                    Math.round(depoisH - antesH) + 'px)');
      }
      wrapper.scrollLeft = estado0.scrollLeft;
    } else {
      falhas.push('cabecalho sem celula .estudante_header nem .blank para prender');
    }

    if (celulaCorpo) {
      // ⚠️ Conferir TODAS as celulas da coluna, e nao so' a primeira.
      //
      // Basta uma escapar para a coluna aparecer desencontrada ao rolar, e olhar so' a
      // primeira nao acha isso. Aconteceu no tcc_consolidado: a marcacao da coluna
      // congelada dependia do TIPO do conteudo, e o rotulo da linha de total, que la' e'
      // um objeto de dado em vez de string, era a unica celula solta da coluna.
      //
      // As faixas de grupo/tutor ficam de fora: sao celula unica com colspan cobrindo a
      // tabela, e quem fica preso nelas e' o rotulo interno, nao a celula.
      var colunaCorpo = [].slice.call(tabela.querySelectorAll('tbody tr'))
        .filter(function (tr) { return !tr.classList.contains('cabecalho'); })
        .map(function (tr) { return tr.children[0]; })
        .filter(function (c) { return c && c.colSpan === 1; });

      var antesTodas = colunaCorpo.map(esqRelativa);
      wrapper.scrollLeft = Math.min(rolaH, 400);
      var depoisTodas = colunaCorpo.map(esqRelativa);
      wrapper.scrollLeft = estado0.scrollLeft;

      var soltas = [];
      for (var k = 0; k < colunaCorpo.length; k++) {
        if (Math.abs(depoisTodas[k] - antesTodas[k]) > 2) {
          soltas.push(colunaCorpo[k].className + ' (saiu ' +
                      Math.round(depoisTodas[k] - antesTodas[k]) + 'px)');
        }
      }

      medidas.colunaDoCorpo = {
        celulas: colunaCorpo.length,
        soltas: soltas.length,
        antes: Math.round(antesTodas[0]),
        depois: Math.round(depoisTodas[0])
      };

      if (soltas.length) {
        falhas.push('coluna do estudante com ' + soltas.length + ' de ' +
                    colunaCorpo.length + ' celulas soltas: ' + soltas.slice(0, 3).join('; '));
      }
    } else {
      // ⚠️ Nao acusar falha quando o relatorio simplesmente nao trouxe dados. As
      // faixas de grupo/tutor (td.tutor) saem mesmo sem estudante embaixo, entao a
      // tabela existe, rola, e nao tem uma unica celula de estudante para prender --
      // o que e' condicao de dados, e nao defeito de CSS. Foi o caso do acesso_tutor
      // numa turma sem registro de log: acusava "corpo sem celula" e mandava procurar
      // um problema que nao existia.
      var linhasDados = tabela.querySelectorAll('tbody tr:not(.cabecalho)').length -
                        tabela.querySelectorAll('tbody td.tutor').length;
      if (linhasDados > 0) {
        falhas.push('corpo sem celula de primeira coluna para prender');
      } else {
        medidas.colunaDoCorpo = 'relatorio sem linhas de dados';
      }
    }
  } else {
    medidas.tituloDaColuna = 'tabela nao rola na horizontal nem apertada';
  }

  wrapper.scrollTop = estado0.scrollTop;
  wrapper.scrollLeft = estado0.scrollLeft;
  wrapper.style.maxWidth = estilo0.maxWidth;
  wrapper.style.maxHeight = estilo0.maxHeight;

  // ⚠️ `falhas: []` sozinho NAO e' aprovacao: numa tabela que nao rola, nada e' posto
  // a' prova e a lista sai vazia igual a' de um relatorio correto. Com o aperto da
  // caixa isso ficou raro, mas continua possivel (tabela de uma linha so').
  var exercitou = (rolaV > 20) || (rolaH > 20);
  var conclusao = !exercitou ? 'NAO EXERCITADO (tabela nao rola nem apertada)'
                            : (falhas.length ? 'REPROVADO' : 'aprovado');

  return {
    relatorio: (location.search.match(/relatorio=([a-z_0-9]+)/) || [])[1] || '?',
    conclusao: conclusao,
    classeTabela: tabela.className,
    cabecalhoEm: tabela.querySelector('thead th') ? 'thead' :
                 (tabela.querySelector('tr.cabecalho') ? 'tbody (.cabecalho)' : 'nenhum'),
    rolagem: {vertical: Math.round(rolaV), horizontal: Math.round(rolaH), apertou: apertou},
    medidas: medidas,
    falhas: falhas
  };
})()
