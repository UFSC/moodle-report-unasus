Relatórios UNA-SUS
==================

Este plugin disponibiliza relatórios dentro dos Cursos Moodle (módulos),
com umai visão global para acompanhamento do andamento de um Curso UFSC,
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
* "mod/forum" (Fórum)
* "mod/quiz" (Quiz)


Instalação
----------

Este plugin deve ser instalado em "report/unasus", juntamente com os
plugins dependentes:

* "local/ufsc"
* "local/tutores"
* "local/relationship"

Este plugin ainda depende do seguinte plugin, mas no futuro o mesmo
deixará de ser dependência (com a remoção dos ultimos acoplamentos com o
Middleware):

* "local/academico"
