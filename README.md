Relatórios UNA-SUS
==================

Este plugin disponibiliza relatórios dentro dos Cursos Moodle (módulos), com uma visão gerencial para acompanhamento do andamento de um Curso Moodle, levando em conta o seu contexto de Turma e os relacionamentos entre tutores e estudantes.

O relacionamento entre tutores e estudantes é realizado através do uso do plugin "local/relationship", onde grupos de tutoria são criados relacionando os tutores e seus estudantes tutorados.

Os relatórios levam em consideração regras de cada curso Moodle, como agrupamentos, a participação ou não do estudante naquele módulo, em grupos de tutoria ou em grupos do curso, prazos definidos pelo "Completion Tracking", regras de definição de notas, entre outras.

Estes relatórios somente monitoram as seguintes atividades:

* "mod/assign" (Texto Online)
* "mod/data" (Database)
* "mod/forum" (Fórum)
* "mod/lti" (Learning Tool Interoperability - external tool)
* "mod/quiz" (Quiz)
* "mod/scorm" (SCORM activity)

Público alvo
------------
Os relatórios são divididos para o seguinte público alvo:

* **Orientadores**: Para acompanhar o andamento de seus estudantes;
* **Tutores**: Para acompanhar o andamento de seus estudantes;
* **Coordenadores**: Para acompanhar os tutores, orientadores e seus estudantes.

Os relatórios são diferenciados dos já existentes no Moodle, pelo fato de possuírem uma visão gerencial dos dados contidos no curso. Sendo que os relatórios fornecidos pelo Moodle apresentam informações operacionais.

### Visão do orientador

O orientador poderá visualizar apenas os dados de seus estudantes, e de apenas dos relatório específicos da ferramenta de TCC, caso tenha sido instalada.

Os dados dos outros orientadores, e dos estudantes desses outros orientadores, ficarão ocultos para o orientador que está acessando o Moodle.

### Visão do tutor

O tutor poderá visualizar apenas os dados de seus estudantes, e de apenas alguns relatórios.

Os dados dos outros tutores, e dos estudantes desses outros tutores, ficarão ocultos para o tutor que está acessando o Moodle.

### Visão do Coordenador

O coordenador terá acesso a todos os tutores, orientadores e seus estudantes.

Tipos de Relatórios
-------------------

Os seguintes tipos de relatórios foram desenvolvidos:

* **Listas**: Apresentando lista de atividades por tutor, sendo que a lista varia 
conforme o relatório.
* **Sínteses**: Apresentando informações sintéticas, relativas aos alunos de cada tutor.
* **Acompanhamentos**: Apresentando o andamento das atividades dos alunos e do trabalho dos 
tutores.
* **Boletim**: Acompanhamento das notas, utilizando os filtros disponíveis para facilitar 
a seleção das informações.
* **Uso do Sistema** (somente coordenação): Visa auxiliar a coordenação do curso no 
acompanhamento da utilização das ferramentas pelos tutores.
* **TCC**: Relativos aos dados da disciplina de Metodologia de Trabalho de Conclusão do 
Curso, com enfoque ao TCC criado pelos estudantes com a supervisão dos orientadores.


Instalação
----------

Este plugin deve ser instalado em "report/unasus", juntamente com os
plugins dependentes:

* ["local/relationship"](https://github.com/UFSC/moodle-local-relationship)
* ["local/tutores"](https://github.com/UFSC/moodle-local-tutores)

## Instalação Opcional

* ["enrol/relationship"](https://github.com/UFSC/moodle-enrol-relationship.git)
* ["local/report_config"](https://github.com/UFSC/moodle-local-report_config.git)

## Permissões

Para que os relatórios possam ser visualizados corretamente as seguintes permissões 
devem ser definidas para os papéis:

|   Capability              | Papel | Descrição |
| --- | --- | --- |
| **report/unasus:view_all** | Coordenadores (tutoria, orientação, curso, avea) | Visualizar relatórios de todos estudantes |
| **report/unasus:view_tutoria** | Tutores | Visualizar relatórios dos estudantes do seu grupo de tutoria |
| **report/unasus:view_orientacao** | Orientadores | Visualizar relatórios dos estudantes do seu grupo de orientação |

## Instalação: informaçôes adicionais

Para maiores informações sobre a instalação os seguintes links podem ser acessados:
* [Instalação](https://github.com/UFSC/moodle-report-unasus/wiki/Installation)
  * [Comandos de instalação](https://github.com/UFSC/moodle-report-unasus/wiki/Installation-commands) 
  * [Configuração do Moodle](https://github.com/UFSC/moodle-report-unasus/wiki/Installation-setup) 

Configuração
------------

Para a perfeita apresentação dos relatórios algumas configurações devem ser 
realizadas nos cursos e atividades onde serão instalados os relatórios gerencias, para
que possam ser apresentados corretamente.

A [Configuração do curso e atividades](https://github.com/UFSC/moodle-report-unasus/wiki/Course-setup) podem ser acessadas neste link.

## Estrutura de Arquivos

| Arquivo / Diretório | Responsabilidade |
|---------------------|-----------------|
| `index.php` | Ponto de entrada, verificação de permissões, roteamento |
| `factory.php` | Singleton que gerencia parâmetros, filtros e estado do relatório |
| `renderer.php` | Geração de HTML para tabelas, gráficos e filtros |
| `locallib.php` | Funções principais de consulta ao banco de dados |
| `datastructures.php` | Modelos de dados: pessoas, estudantes, atividades |
| `activities_datastructures.php` | Modelos específicos de cada tipo de atividade |
| `lib.php` | Lista de relatórios válidos e integração com navegação Moodle |
| `relatorios/queries.php` | Consultas SQL do plugin |
| `relatorios/loops.php` | Agregação e iteração sobre os dados consultados |
| `relatorios/relatorios.php` | Geração de tabelas e gráficos por relatório |
| `sistematcc.php` | Cliente do webservice externo de TCC |
| `lang/en/report_unasus.php` | Strings de interface (usadas como fallback; strings em português) |
| `tests/` | Testes PHPUnit e Behat (ver TESTS.md) |

## Solução de Problemas

**Relatório exibe "Sem dados" mesmo com atividades configuradas**
: Verifique se o "Completion Tracking" está habilitado no curso e se as atividades têm datas de conclusão definidas. Sem `completionexpected`, o plugin não consegue calcular prazos.

**Tutor não visualiza seus estudantes**
: Confirme que o grupo de tutoria foi criado via `local/relationship` e que o tutor está vinculado como membro do papel correto no grupo. Grupos criados diretamente no Moodle (sem o plugin `local/tutores`) não são reconhecidos.

**Erro ao gerar relatório TCC**
: O relatório TCC depende de webservice externo. Verifique a configuração de `sistematcc.php` e se o serviço está acessível pela rede do servidor Moodle.

**Relatório de boletim mostra nota diferente do gradebook**
: O plugin usa a API de notas do Moodle. Certifique-se de que o método de agregação e os pesos estão configurados corretamente no gradebook do curso e que as notas foram recalculadas (`Grades → Regrade`).

**Permissão negada ao acessar relatório**
: Verifique a atribuição das capabilities na tabela de permissões acima. O papel do usuário no curso deve ter a capability correspondente (`view_all`, `view_tutoria` ou `view_orientacao`).

## Changelog

### 2026-04-29
- Documentação: README.md completo com estrutura de arquivos, solução de problemas e changelog
- Testes: Expandidos de 5 para 22 testes PHPUnit (39 → 79 asserções), incluindo 6 testes de borda (limites de deadline, grade=0, is_grade_needed no limite)
- Testes: 17 feature files Behat cobrindo todos os relatórios (61 cenários)
- Behat: Cenário de borda para atividades_concluidas_agrupadas com zero conclusões
- Performance: correlated subquery substituída por LEFT JOIN explícito em `query_alunos_relationship()` e `query_alunos_relationship_student()`
- Performance: memoização de `report_unasus_get_tcc_definition()` com cache estático
- Performance: nova função `report_unasus_get_lti_type_config()` elimina N+1 queries de configuração LTI por `typeid`
- Performance: memoização de `SistemaTccClient::get_tcc_definition()` elimina chamadas WebService duplicadas

### 2026-04-17 (v2026041701)
- Estabilização dos intervalos de datas do relatório de acesso do tutor
- Normalização dos limites de dia de uso do tutor
- Expansão da cobertura Behat com passos reutilizáveis

### Versões anteriores
Consulte o histórico de commits (`git log --oneline`) para mudanças anteriores.
