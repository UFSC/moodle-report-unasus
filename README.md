Relatórios UNA-SUS
==================

Este plugin disponibiliza relatórios dentro dos Cursos Moodle (módulos), com uma visão gerencial para acompanhamento do andamento de um Curso Moodle, levando em conta o seu contexto de Turma e os relacionamentos entre tutores e estudantes.

O relacionamento entre tutores e estudantes é realizado através do uso do plugin "local/relationship", onde grupos de tutoria são criados relacionando os tutores e seus estudantes tuturados.

Os relatótios levam em consideração regras de cada curso Moodle, como agrupamentos, a participação ou não do estudante naquele módulo, em grupos de tutoria ou em grupos do curso, prazos definidos pelo "Completion Tracking", regras de definição de notas, entre outras.

Estes relatórios somente monitoram as seguintes atividades:

* "mod/assign" (Texto Online)
* "mod/data" (Database)
* "mod/forum" (Fórum)
* "mod/lti" (Learning Tool Interoperability - external tool)
* "mod/quiz" (Quiz)
* "mod/scorm" (SCORM activity)


Público alvo
------------
Os relatórios são divididos para os seguintes público alvo:

* **Orientadores**: Para acompanhar o andamento de seus estudantes; 
* **Tutores**: Para acompanhar o andamento de seus estudantes; 
* **Coordenadores**: Para acompanhar os tutores e seus estudantes.

Os relatórios são diferenciados dos já existentes no Moodle, pelo fato de possuírem uma visão gerencial dos dados contidos no curso. Sendo que os relatórios fornecidos pelo Moodle apresentam informações operacionais.

### Visão do orientador

O orientador poderá visualizar apenas os dados de seus estudantes, e de apenas dos relatório específicos da ferramenta de TCC, caso tenha sido instalada.

Os dados dos outros orientadores, e dos estudantes desses outros orientadores, ficarão ocultos para o orientador que está acessando o Moodle.

### Visão do tutor

O tutor poderá visualizar apenas os dados de seus estudantes, e de apenas alguns relatórios.

Os dados dos outros tutores, e dos estudantes desses outros tutores, ficarão ocultos para o tutor que está acessando o Moodle.

### Visão do Coordenador

O coordenador terá acesso a todos os tutores e seus estudantes.

Instalação
----------

Este plugin deve ser instalado em "report/unasus", juntamente com os
plugins dependentes:

* "local/relationship"
* "local/report-config"
* "local/tutores"
* "local/ufsc"

Permissões
----------

Para que os relatórios possam ser visualizados corretamente as seguintes permissões devem ser definidas para os papéis:

|   Capability              | Papel | Descrição |
| --- | --- | --- |
| **report/unasus:view** | Tutores, Orientadores e Coordenadores (tutoria, curso, avea) | Visualizar listagem de relatórios da UNA-SUS | 
| **report/unasus:view_all** | Coordenadores (tutoria, curso, avea) | Visualizar relatórios de todos estudantes |
| **report/unasus:view_tutoria** | Tutores | Visualizar relatórios dos estudantes do seu grupo de tutoria |
| **report/unasus:view_orientacao**| Orientadores | Visualizar relatórios dos estudantes do seu grupo de orientação |

Configuração
------------

Para a perfeita apresentação dos relatórios as seguintes configurações apresentadas a seguir devem ser verificadas nas atividades dos cursos onde serão instalados os relatórios gerencias da UnA-SUS.

### Configuração das atividades

Somente atividades monitoradas com nota definida serão apresentadas nos relatórios UnA-SUS. Atividades sem nota serão suprimidas.

Além disso, devem também possuir a data em que se espera concluir a atividade, para que os relatórios de prazo possam ser considerados dentro ou fora do prazo de entrega nos relatórios. Para isso a opção de configuração da atividade “Conclusão da atividade no curso” deve ser informada.


### Livro de notas

As notas apresentadas em todos os relatórios do plugin seguirão conforme a definição do Livro de Notas. Isso inclui a forma em que a nota é apresentada, isto é, seu peso, sua escala entre outras definições que podem ser realizadas no livro de notas.

As notas apresentadas em todos os relatórios do plugin serão as definidas nas atividades e não no livro de notas , isto é, caso uma nota seja sobreposta no livro de notas ela não será utilizada nos relatórios, mas sim a nota dada pelo tutor, ou pelo sistema no caso de avaliação automática, na própri atividade.  

### Plugin report-config

Este plugin auxilia em permitir apresentar ou ocultar as atividades de um curso Moodle. 
 
Uma lista com todas as atividades de todos os módulos do curso Moodle serão apresentdas, para que possam ser selecionadas. 

As atividades selecionadas seão apesentadas nos relatórios do plugin de relatórios, enquanto as atividades não selecionadas serão desconsideradas durante a montagem, isto é, ficarão ocultas. 

### Plugin de relacionamento (local/relationship)

Relacionamentos devem ser criados para os relatório poderem relacionar tanto os tutores quanto os orientadores a seus estudantes.

Os relacionamentos devem ser criados na categoria de curso. Caso um curso tenha várias turmas ou edições, uma categoria deve ser criada para a identificação de cada turma/edição, e nesta categoria é que os relacionamentos devem ser criados, para que possam englobar todos os módulos de cada turma/edição e identificar corretamente os relacionamentos desta turma/edição, se houver esta distinção.  

Para que haja a correta identificação de cada tipo de relacionamento, isto é, de tutores e de orientadores, uma etiqueta ("tag" do Moodle) deve identificar cada qual, conforme a tabela abaixo:

| Tipo de Relacionamento | TAG de identificação|
| --- | --- |
| Tutoria | grupo_tutoria |
| Orientação | grupo_orientacao |