@unasus @report_unasus @boletim
Feature: Relatório UNA-SUS Boletim
  Para acompanhar notas e média final dos estudantes no sistema UNA-SUS
  Como coordenador ou tutor
  Preciso gerar e exportar o relatório de boletim

Background:
  Given a standard report_unasus tutoria fixture exists

  And I submit assignment "a2" for user "student2"
  And I submit assignment "a3" for user "student3"
  And I submit assignment "a4" for user "student1"
  And I set the submission date of activity "a4" to "0" days after
  And I submit assignment "a5" for user "student1"
  And I set the submission date of activity "a5" to "10" days after
  And I submit assignment "a6" for user "student1"
  And I set the submission date of activity "a6" to "11" days after

  @javascript @boletim
Scenario: boletim - verificacao de notas
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I set the grade of activity "a1" for user "student2" to "40"
    And I recalculate gradebook final grades for course "c1"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    # student1 tem notas 100 (a4) e 80 (a5) -> media final deve ser 90.0
    Then the unasus report table should have "100.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "80.0" at row "Student s1" and column "Test assignment five"
    And the unasus report table should have "40.0" at row "Student s2" and column "Test assignment one"
    And the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage
    # CSS class cobre na_media, abaixo_media_nota, sem_nota, abaixo_media (nota final).
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment five" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment one" should have css class "sem_nota"
    And the unasus report table cell at row "Student s2" and column "Test assignment one" should have css class "abaixo_media_nota"
    And the unasus report table cell at row "Student s1" and column "M.Final" should have css class "na_media"
    And the unasus report table cell at row "Student s2" and column "M.Final" should have css class "abaixo_media"
    And I should see "Atividade avaliada com nota acima de"
    And I should see "Atividade avaliada com nota abaixo de"
    And I should see "Média final abaixo de"
    And I should see "Atividade não avaliada ou não entregue"
    And I should see "Atividade não aplicada"

  @boletim @csv
  Scenario: boletim exporta CSV com dados esperados
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Estudante"
    And the exported unasus csv should contain "Média Final"
    And the exported unasus csv should contain "Test assignment four"
    And the exported unasus csv should have "100.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "90.0" at row "Student s1" and column "Média Final"
    And the exported unasus csv should have an empty value at row "Student s1" and column "Test assignment one"

  @javascript @boletim
Scenario: boletim - média ponderada no gradebook
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set gradebook weight "75" for activity "a4" in course "c1"
    And I set gradebook weight "25" for activity "a5" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "95.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    And the unasus report table should have "100.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "80.0" at row "Student s1" and column "Test assignment five"
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment five" should have css class "na_media"
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - média ponderada no gradebook exporta CSV
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set gradebook weight "75" for activity "a4" in course "c1"
    And I set gradebook weight "25" for activity "a5" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "95.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should contain "Média Final"
    And the exported unasus csv should have "100.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "95.0" at row "Student s1" and column "Média Final"

  @javascript @boletim
Scenario: boletim - média simples com vazias=zero
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "90.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then the unasus report table should have "100.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "80.0" at row "Student s1" and column "Test assignment five"
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment five" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment one" should have css class "sem_nota"
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - média simples com vazias=zero exporta CSV
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "90.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @javascript @boletim
Scenario: boletim - média ponderada com vazias=zero
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I reset all gradebook weights for course "c1"
    And I set gradebook weight "60" for activity "a4" in course "c1"
    And I set gradebook weight "20" for activity "a5" in course "c1"
    And I set gradebook weight "20" for activity "a1" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "95.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    And the unasus report table should have "100.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "80.0" at row "Student s1" and column "Test assignment five"
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment five" should have css class "na_media"
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - média ponderada com vazias=zero exporta CSV
    And I set gradebook aggregation for course "c1" to weighted mean of grades
    And I set gradebook include empty grades for course "c1" to enabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I reset all gradebook weights for course "c1"
    And I set gradebook weight "60" for activity "a4" in course "c1"
    And I set gradebook weight "20" for activity "a5" in course "c1"
    And I set gradebook weight "20" for activity "a1" in course "c1"
    And I set the grade of activity "a4" for user "student1" to "100"
    And I set the grade of activity "a5" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be lower than "95.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @javascript @boletim
Scenario: boletim - atividades base 100 com nota final base 10
    # Atividades têm grademax=100; course total configurado para base 10 (grademax=10).
    # finalgrade armazenado em escala 0-10 → boletim exibe valor bruto: 7.0 (não 70.0).
    # student1: a4=80/100 (80%), a5=60/100 (60%) → média=70% → finalgrade=7.0
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "10"
    And I set the grade of activity "a4" for user "student1" to "80"
    And I set the grade of activity "a5" for user "student1" to "60"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "70.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    Then the unasus report table should have "80.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "60.0" at row "Student s1" and column "Test assignment five"
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment five" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "M.Final" should have css class "na_media"
    And the unasus report table should have "7.0" at row "Student s1" and column "M.Final"

  @boletim @csv
  Scenario: boletim - atividades base 100 com nota final base 10 exporta CSV
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "10"
    And I set the grade of activity "a4" for user "student1" to "80"
    And I set the grade of activity "a5" for user "student1" to "60"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "70.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should have "7.0" at row "Student s1" and column "Média Final"

  @javascript @boletim
Scenario: boletim - todas as atividades avaliadas verifica media final
    # student1: a1=60, a2=70, a3=80, a4=90, a5=100, a6=80
    # media simples (6 notas) = 480/6 = 80.0%
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a1" for user "student1" to "60"
    And I set the grade of activity "a2" for user "student1" to "70"
    And I set the grade of activity "a3" for user "student1" to "80"
    And I set the grade of activity "a4" for user "student1" to "90"
    And I set the grade of activity "a5" for user "student1" to "100"
    And I set the grade of activity "a6" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "80.0"
    And I log in as "admin"
    And I follow "Courses"
    And I follow "Category 1"
    And I follow "Course1"
    And I navigate to "Boletim" node in "Reports > UNA-SUS"
    And I press "Gerar relatório"
    And the unasus report table should have "90.0" at row "Student s1" and column "Test assignment four"
    And the unasus report table should have "60.0" at row "Student s1" and column "Test assignment one"
    And the unasus report table cell at row "Student s1" and column "Test assignment four" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "Test assignment one" should have css class "na_media"
    And the unasus report table cell at row "Student s1" and column "M.Final" should have css class "na_media"
    Then the unasus report table final grade for user "student1" in course "c1" should match Moodle gradebook percentage

  @boletim @csv
  Scenario: boletim - todas as atividades avaliadas verifica media final exporta CSV
    # student1: a1=60, a2=70, a3=80, a4=90, a5=100, a6=80
    # media simples (6 notas) = 480/6 = 80.0%
    And I set gradebook aggregation for course "c1" to mean of grades
    And I set gradebook include empty grades for course "c1" to disabled
    And I set gradebook course total grademax for course "c1" to "100"
    And I set the grade of activity "a1" for user "student1" to "60"
    And I set the grade of activity "a2" for user "student1" to "70"
    And I set the grade of activity "a3" for user "student1" to "80"
    And I set the grade of activity "a4" for user "student1" to "90"
    And I set the grade of activity "a5" for user "student1" to "100"
    And I set the grade of activity "a6" for user "student1" to "80"
    And I recalculate gradebook final grades for course "c1"
    And the Moodle gradebook final grade percentage for user "student1" in course "c1" should be "80.0"
    And I log in as "admin"
    And I export the unasus report "boletim" as csv for course "c1" with params:
      | name | value |
    Then the exported unasus csv should have "60.0" at row "Student s1" and column "Test assignment one"
    And the exported unasus csv should have "70.0" at row "Student s1" and column "Test assignment two"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment three"
    And the exported unasus csv should have "90.0" at row "Student s1" and column "Test assignment four"
    And the exported unasus csv should have "100.0" at row "Student s1" and column "Test assignment five"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Test assignment six"
    And the exported unasus csv should have "80.0" at row "Student s1" and column "Média Final"
