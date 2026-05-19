@unasus @report_unasus @relationship
Feature: Relacionamento com múltiplos cohorts por papel
  Para que coordenadores enxerguem todos os estudantes/tutores/orientadores
  Como administrador
  Preciso que os relatórios respeitem N cohorts no mesmo papel do relationship
  — não apenas o primeiro cohort de cada papel.

  # Cada cenário monta sua própria fixture porque os cenários variam o papel
  # com múltiplos cohorts (estudante, tutor, orientador) — fixturas mutuamente exclusivas.

  @javascript @relationship @estudante
  Scenario: boletim lista todos os 12 estudantes dos 3 cohorts de estudante
    # Fixture: 3 cohorts no papel estudante (CHs1/2/3); student1..4 → group1, 5..8 → group2, 9..12 → group3.
    Given a unasus multi-cohort estudante environment exists
    And I submit assignment "a1" for user "student1"
    And I submit assignment "a2" for user "student5"
    And I submit assignment "a3" for user "student9"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    When I press "Gerar relatório"
    Then the unasus report should have row "Student s1"
    And the unasus report should have row "Student s2"
    And the unasus report should have row "Student s3"
    And the unasus report should have row "Student s4"
    And the unasus report should have row "Student s5"
    And the unasus report should have row "Student s6"
    And the unasus report should have row "Student s7"
    And the unasus report should have row "Student s8"
    And the unasus report should have row "Student s9"
    And the unasus report should have row "Student s10"
    And the unasus report should have row "Student s11"
    And the unasus report should have row "Student s12"

  @javascript @relationship @estudante
  Scenario: boletim mostra notas representativas de cada cohort de estudante
    Given a unasus multi-cohort estudante environment exists
    And I submit assignment "a1" for user "student1"
    And I submit assignment "a2" for user "student5"
    And I submit assignment "a3" for user "student9"
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a1" for user "student1" to "80"
    And I set the grade of activity "a2" for user "student5" to "70"
    And I set the grade of activity "a3" for user "student9" to "60"
    And I recalculate gradebook final grades for course "c1"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    When I press "Gerar relatório"
    Then the unasus report table should have "80.0" at row "Student s1" and column "Test assignment one"
    And the unasus report table should have "70.0" at row "Student s5" and column "Test assignment two"
    And the unasus report table should have "60.0" at row "Student s9" and column "Test assignment three"

  @javascript @relationship @tutor
  Scenario: boletim agrupa estudantes por tutores de cohorts/roles distintos com suas atividades
    # Fixture multi-cohort tutor:
    #   teacher1 (CHt1, role=editingteacher) → group1, students 1-4
    #   teacher2 (CHt2, role=teacher)        → group2, students 5-8
    #   teacher3 (CHt3, role=editingteacher) → group3, students 9-12
    # Sob singular wrapper, get_grupos_tutoria_by_userid só encontra grupos do cohort 1
    # → groups 2 e 3 somem do boletim → students 5-12 não aparecem.
    Given a unasus multi-cohort tutor environment exists
    And I submit assignment "a1" for user "student1"
    And I submit assignment "a2" for user "student5"
    And I submit assignment "a3" for user "student9"
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a1" for user "student1" to "80"
    And I set the grade of activity "a2" for user "student5" to "70"
    And I set the grade of activity "a3" for user "student9" to "60"
    And I recalculate gradebook final grades for course "c1"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    When I press "Gerar relatório"
    # Estudantes de cada grupo (sob tutor de cohort diferente) presentes.
    Then the unasus report should have row "Student s1"
    And the unasus report should have row "Student s5"
    And the unasus report should have row "Student s9"
    And the unasus report should have row "Student s12"
    # Atividade do estudante de cada tutor cohort renderiza com a nota correta.
    And the unasus report table should have "80.0" at row "Student s1" and column "Test assignment one"
    And the unasus report table should have "70.0" at row "Student s5" and column "Test assignment two"
    And the unasus report table should have "60.0" at row "Student s9" and column "Test assignment three"
