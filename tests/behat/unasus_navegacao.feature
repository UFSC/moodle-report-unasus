@unasus @report_unasus @javascript @navegacao
Feature: Os relatórios UNA-SUS são alcançáveis pela navegação do curso
  Para que o usuário encontre os relatórios sem decorar URL
  Como usuário com permissão de ver relatórios
  Preciso chegar neles pela lista de Relatórios do curso

# ⚠️ Este é o ÚNICO cenário que exercita a navegação; os demais features abrem o
# relatório direto por URL, de propósito, para não repetirem 43 vezes a mesma travessia
# de menu -- que é lenta e frágil. A contrapartida é que a cobertura de "dá para chegar
# lá clicando" vive toda aqui: quebrando este cenário, ninguém mais acusa.
#
# O que ele protege: a página /report/view.php monta a lista com o template
# core/report_link_page, que percorre UM nível e emite <a href="{{action}}">. O nó do
# plugin é um container cujos relatórios ficam um nível abaixo; sem action própria ele
# aparecia ali como <a href="">UNA-SUS</a>, um item morto.

Background:
  Given a standard report_unasus tutoria fixture exists

  Scenario: a lista de Relatórios do curso leva ao índice, e o índice ao relatório
    Given I log in as "admin"
    And I am on "Course1" course homepage
    When I navigate to "Reports" in current page administration
    Then I should see "UNA-SUS" in the "region-main" "region"

    When I click on "UNA-SUS" "link" in the "region-main" "region"
    Then I should see "Relatórios UNA-SUS"
    And I should see "Boletim"
    And I should see "TCC: Entrega de Atividades"

    When I click on "Boletim" "link" in the "region-main" "region"
    # ⚠️ Botao, e nao texto: "Gerar relatorio" e' o value de um <input type="submit">, que
    # nao tem no' de texto -- "I should see" nao o enxerga.
    Then "Gerar relatório" "button" should exist
