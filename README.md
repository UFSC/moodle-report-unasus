Relatórios UNA-SUS
==================

Este plugin disponibiliza relatórios dentro dos Cursos Moodle (módulos),
com uma visão global para acompanhamento do andamento de um Curso UFSC,
levando em conta o seu contexto de Turma e os relacionamentos essenciais.

Além da relação de estudantes, tutores e coordenadores com cada módulo,
através do uso do plugin "local/relationship", estes
relatórios utilizam a relação "Tutor x Aluno".

Ele também leva em consideração regras de cada curso Moodle, como
agrupamentos, a participação ou não do estudante naquele módulo,
prazos definidos pelo "Completion Tracking", regras de definição de
notas, entre outras.

Estes relatórios somente monitoram as seguintes atividades:

* "mod/assign" (Texto Online)
* "mod/data" (Database)
* "mod/forum" (Fórum)
* "mod/lti" (Learning Tool Interoperability - external tool)
* "mod/quiz" (Quiz)
* "mod/scorm" (SCORM activity)


Instalação
----------

Este plugin deve ser instalado em "report/unasus", juntamente com os
plugins dependentes:

* "local/relationship"
* "local/report-config"
* "local/tutores"
* "local/ufsc"
