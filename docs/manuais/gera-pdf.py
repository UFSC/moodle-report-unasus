# -*- coding: utf-8 -*-
"""Gera o PDF do roteiro "Relatorios UNA-SUS -- o que mudou", com sumario
clicavel e numero de pagina real.

    python3 docs/manuais/gera-pdf.py

Adaptado do gerador do Sistema TCC (docs/treinamento/dados-de-teste/gera-pdf.py
no repositorio sistema-tcc-r8). Mesmo processo, em ordem:

  1. poe `id` em cada cartao e em cada parte;
  2. remonta o sumario a partir dos TITULOS REAIS, com link para o `id` e um
     espaco reservado para o numero da pagina;
  3. gera o PDF;
  4. le' o PDF com `pdftotext` e descobre em que pagina cada item caiu;
  5. reinjeta os numeros e gera o PDF de novo;
  6. CONFERE que os numeros continuam certos no PDF final;
  7. carimba "pagina N de M" no rodape (ver `numera_paginas`).

⚠️ Esta v1 NAO tem capturas de tela (sao protótipos em ASCII, dentro de
<pre>), entao nao ha verificacao de imagens/ como no gerador original.

⚠️ O `file://` tem de apontar para DENTRO do repositorio: se algum dia entrarem
imagens por caminho relativo, rodar de outro diretorio quebra isso em silencio.
"""
import os, re, subprocess, sys, tempfile

HTML = 'docs/manuais/relatorios-unasus-o-que-mudou.html'
BASE = 'docs/manuais/relatorios-unasus-o-que-mudou'
PERFIL = os.path.join(tempfile.gettempdir(), 'chrome-pdf-manuais-unasus')

def versao(html):
    """A versao sai da CAPA, e o historico so' confirma.

    ⚠️ Os dois lugares concordam por COPIA -- ver a licao no gerador do
    Sistema TCC, onde ja' divergiram uma vez. Aqui a divergencia PARA a
    geracao, em vez de virar um PDF cujo nome discorda da capa."""
    capa = re.search(r'<b>Versão</b>\s*([0-9]+\.[0-9]+)', html)
    if not capa:
        sys.exit('nao achei a versao na capa (<b>Versão</b> X.Y)')
    tabela = re.search(r'<tr><td>([0-9]+\.[0-9]+)</td><td>', html)
    if tabela and tabela.group(1) != capa.group(1):
        sys.exit(f'capa diz {capa.group(1)} e o historico diz {tabela.group(1)} -- '
                 'os dois precisam concordar antes de gerar')
    return capa.group(1)

PDF = None   # definido na primeira leitura do HTML


def arquiva_versoes_anteriores(atual):
    """No raiz de docs/manuais fica UMA versao -- a ABERTA. As fechadas moram
    no `historico/`. Move, e nao copia -- ver a licao no gerador do TCC."""
    pasta = os.path.dirname(HTML)
    destino = os.path.join(pasta, 'historico')
    if not os.path.isdir(destino):
        return

    for nome in sorted(os.listdir(pasta)):
        m = re.fullmatch(re.escape(os.path.basename(BASE)) + r'-v(\d+\.\d+)\.pdf', nome)
        if not m or m.group(1) == atual:
            continue

        origem = os.path.join(pasta, nome)
        alvo = os.path.join(destino, nome)
        if os.path.exists(alvo):
            if paginas_do_pdf(origem) != paginas_do_pdf(alvo):
                sys.exit(f'{nome} existe no historico com CONTEUDO diferente -- '
                         'nao vou sobrescrever nem descartar; resolva a mao')
            os.remove(origem)
        else:
            os.replace(origem, alvo)
        print(f'  {nome} -> historico/ (versao fechada sai do raiz)')

def confere_contagens(html):
    """O documento afirma coisas sobre si mesmo -- e elas envelhecem em silencio."""
    itens = len(re.findall(r'<span class="num">\d+</span>', html))
    erros = []

    linha = re.search(r'<tr><td>\d+\.\d+</td>.*?</tr>', html, re.S)
    texto = re.sub(r'\s+', ' ', re.sub(r'<[^>]*>', ' ', linha.group(0))) if linha else ''
    m = re.search(r'(\d+)\s+itens', texto)
    if m and int(m.group(1)) != itens:
        erros.append(f'a linha do historico diz {m.group(1)} itens e existem {itens}')

    if erros:
        sys.exit('contagens nao batem:\n  - ' + '\n  - '.join(erros))
    print(f'contagens conferidas: {itens} itens')

def limpa(html):
    html = re.sub(r'<span class="novo">.*?</span>', '', html, flags=re.S)
    return re.sub(r'\s+', ' ', re.sub(r'<[^>]*>', '', html)).strip()

def gera_pdf():
    r = subprocess.run(['google-chrome', '--headless=new', '--disable-gpu', '--no-sandbox',
                        '--no-pdf-header-footer', f'--user-data-dir={PERFIL}',
                        f'--print-to-pdf={os.path.abspath(PDF)}',
                        'file://' + os.path.abspath(HTML)],
                       capture_output=True, text=True)
    if not os.path.exists(PDF):
        sys.exit('falhou ao gerar o PDF:\n' + r.stderr)

def paginas_do_pdf(arquivo=None):
    txt = subprocess.run(['pdftotext', '-layout', arquivo or PDF, '-'],
                         capture_output=True, text=True).stdout
    pgs = txt.split('\f')
    if pgs and not pgs[-1].strip():
        pgs.pop()
    return [re.sub(r'\s+', ' ', p) for p in pgs]

def acha_pagina(paginas, agulha, a_partir_de=0):
    a = re.sub(r'\s+', ' ', agulha)[:60]
    for i in range(a_partir_de, len(paginas)):
        if a and a in paginas[i]:
            return i + 1
    return None

def estrutura(s):
    """[(titulo_parte, id_parte, [(n, titulo, id_item), ...]), ...]"""
    partes = []
    for m in re.finditer(r'<section class="parte"[^>]*>\s*<h2>(.*?)</h2>', s, re.S):
        partes.append([limpa(m.group(1)), [], m.end()])
    for i, pt in enumerate(partes):
        fim = partes[i + 1][2] if i + 1 < len(partes) else len(s)
        for c in re.finditer(r'<span class="num">(\d+)</span>(.*?)</p>', s[pt[2]:fim], re.S):
            pt[1].append((c.group(1), limpa(c.group(2))))
    return partes

def poe_ids(s):
    s = re.sub(r'<div class="teste"(?: id="item-\d+")?>(\s*<p class="titulo"><span class="num">(\d+)</span>)',
               lambda m: '<div class="teste" id="item-%s">%s' % (m.group(2), m.group(1)), s)
    n = [0]
    def parte(m):
        n[0] += 1
        return '<section class="parte" id="parte-%d">' % n[0]
    return re.sub(r'<section class="parte"(?: id="parte-\d+")?>', parte, s)

def monta_sumario(s, paginas=None):
    partes = estrutura(s)
    L = ['<div class="sumario">',
         '  <strong style="font-size:11pt">O que tem neste roteiro</strong>']
    for idx, (titulo, itens, _) in enumerate(partes, start=1):
        pg = ''
        if paginas:
            p = paginas.get(('parte', idx))
            pg = f'<span class="pg">{p}</span>' if p else '<span class="pg">--</span>'
        L.append(f'  <p class="sumario__parte">'
                 f'<a href="#parte-{idx}">{titulo}</a><span class="pontos"></span>{pg}</p>')
        if itens:
            L.append('  <ul class="sumario__itens">')
            for n, t in itens:
                pg = ''
                if paginas:
                    p = paginas.get(('item', n))
                    pg = f'<span class="pg">{p}</span>' if p else '<span class="pg">--</span>'
                else:
                    pg = '<span class="pg">00</span>'
                L.append(f'    <li><a href="#item-{n}"><b>{n}.</b> {t}</a>'
                         f'<span class="pontos"></span>{pg}</li>')
            L.append('  </ul>')
    L.append('</div>')
    ini = s.index('<div class="sumario">')
    fim = s.index('\n</div>', ini) + len('\n</div>')
    return s[:ini] + '\n'.join(L) + s[fim:], partes

def mapeia(partes, paginas):
    """titulo -> pagina, em ordem de leitura. A busca comeca DEPOIS da pagina
    do sumario, senao cada item casa nele mesmo."""
    inicio = 0
    for i, pg in enumerate(paginas):
        if 'O que tem neste roteiro' in pg:
            inicio = i + 1
            break
    mapa, cursor = {}, inicio
    for idx, (titulo, itens, _) in enumerate(partes, start=1):
        p = acha_pagina(paginas, titulo, cursor)
        if p:
            mapa[('parte', idx)] = p
            cursor = p - 1
        for n, t in itens:
            p = acha_pagina(paginas, t, cursor)
            if p:
                mapa[('item', n)] = p
                cursor = p - 1
    return mapa

# ⚠️ O RODAPE NAO SAI DO CHROME (ver o gerador do Sistema TCC para o porque).
# Por isso e' CARIMBADO depois, com ghostscript.
PROLOGO_PS = r"""
/Helvetica findfont dup length dict copy begin
  /Encoding ISOLatin1Encoding def currentdict
end
/HelvLat exch definefont pop
/TOT (%(total)d) def
/PRE (p\341gina ) def
/MEI ( de ) def
/carimbo {
  /HelvLat findfont 8 scalefont setfont
  20 string cvs /N exch def
  PRE stringwidth pop N stringwidth pop add MEI stringwidth pop add TOT stringwidth pop add
  2 div 297.64 exch sub 30 moveto
  PRE show N show MEI show TOT show
} bind def
<< /EndPage {
  0 eq {
    1 add
    dup 1 gt { gsave 0.35 setgray carimbo grestore } { pop } ifelse
    true
  } { pop false } ifelse
} bind >> setpagedevice
"""

def avisa_paginas_vazias(paginas):
    """Pagina quase vazia e' sintoma de quebra ruim -- avisa, nao aborta."""
    suspeitas = []
    for i, texto in enumerate(paginas, start=1):
        corpo = re.sub(r'página \d+ de \d+', '', texto).strip()
        if len(corpo) < 80:
            suspeitas.append(i)

    if suspeitas:
        print('  ⚠ paginas quase VAZIAS (quebra ruim?): ' +
              ', '.join(str(n) for n in suspeitas))
        print('    confira o cartao ANTERIOR -- costuma ser ele transbordando.')


def numera_paginas(total):
    """Carimba "pagina N de M" centralizado no rodape, menos na CAPA."""
    fd, prologo = tempfile.mkstemp(prefix='rodape-manuais-unasus-', suffix='.ps')
    try:
        with os.fdopen(fd, 'w', encoding='latin-1') as f:
            f.write(PROLOGO_PS % {'total': total})

        saida = PDF + '.numerado'
        r = subprocess.run(['gs', '-q', '-o', saida, '-sDEVICE=pdfwrite',
                            '-dAutoFilterColorImages=false', '-dAutoFilterGrayImages=false',
                            '-dColorImageFilter=/FlateEncode', '-dGrayImageFilter=/FlateEncode',
                            '-dDownsampleColorImages=false', '-dDownsampleGrayImages=false',
                            '-dDownsampleMonoImages=false',
                            '-dColorConversionStrategy=/LeaveColorUnchanged',
                            '-f', prologo, '-f', PDF],
                           capture_output=True, text=True)

        def morre(msg):
            if os.path.exists(saida):
                os.remove(saida)
            sys.exit(msg + '\n' + (r.stdout or '') + (r.stderr or ''))

        if r.returncode != 0:
            morre(f'o ghostscript saiu com codigo {r.returncode}')
        if not os.path.exists(saida):
            morre('o ghostscript nao gerou o PDF numerado')

        depois = paginas_do_pdf(saida)
        if len(depois) != total:
            morre(f'o carimbo comeu paginas: eram {total} e sobraram {len(depois)}')

        if f'página {total} de {total}' not in depois[-1]:
            morre(f'a ultima pagina saiu sem o rodape "pagina {total} de {total}"')
        if 'página' in depois[0]:
            morre('a CAPA saiu com rodape -- a condicao do EndPage esta invertida')
        if total > 1 and f'página 2 de {total}' not in depois[1]:
            morre(f'a pagina 2 saiu sem o rodape "pagina 2 de {total}"')

        os.replace(saida, PDF)
    finally:
        if os.path.exists(prologo):
            os.remove(prologo)

    print(f'rodape carimbado em {total - 1} paginas (a capa fica sem numero)')

s = open(HTML, encoding='utf-8').read()
confere_contagens(s)
VERSAO = versao(s)
PDF = f'{BASE}-v{VERSAO}.pdf'
print('gerando', PDF)
arquiva_versoes_anteriores(VERSAO)
s = poe_ids(s)
s, _ = monta_sumario(s)                       # 1a passada: espaco reservado
open(HTML, 'w', encoding='utf-8').write(s)
gera_pdf()

partes = estrutura(s)
mapa = mapeia(partes, paginas_do_pdf())
s, partes = monta_sumario(s, mapa)            # 2a passada: numeros reais
open(HTML, 'w', encoding='utf-8').write(s)
gera_pdf()

mapa2 = mapeia(partes, paginas_do_pdf())
erros = [k for k in mapa if mapa.get(k) != mapa2.get(k)]
faltando = [f'{k[0]} {k[1]}' for _, k in [(0, k) for k in
            [('item', n) for _, itens, _ in partes for n, _t in itens]] if k not in mapa2]
print(f'itens no sumario: {sum(len(i) for _, i, _ in partes)}')
print(f'paginas do PDF: {len(paginas_do_pdf())}')
print('numeros que mudaram entre as duas passadas:', erros or 'nenhum')
print('sem pagina encontrada:', faltando or 'nenhum')

paginas_finais = paginas_do_pdf()
numera_paginas(len(paginas_finais))
avisa_paginas_vazias(paginas_finais)
