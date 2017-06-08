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

Os seguintes tipos de relatórios foram 

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
