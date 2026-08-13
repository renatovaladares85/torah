# Torah

Torah é um plugin comunitário para GLPI 10 que adiciona restrições
complementares e configuráveis a campos e ações de chamados.

Torah nunca concede permissões. A autorização nativa do GLPI permanece como a
primeira e autoritativa decisão. Torah apenas rejeita uma alteração quando o
GLPI a permite e uma política aplicável bloqueia a regra correspondente.

A documentação fonte e autoritativa em inglês está em [README.md](README.md).

## Compatibilidade

| Torah | GLPI | PHP |
|---|---|---|
| 0.4.9 | >= 10.0.20 e < 10.0.99 | >= 8.2 |
| 0.1.x | >= 10.0.20 e < 10.0.99 | >= 8.2 |

A versão 0.4.9 do Torah foi validada com GLPI 10.0.20. Não há compatibilidade
declarada fora desta matriz.

A versão 0.4.9 é uma release estável. Instale-a somente a partir do pacote de
produção validado na release publicada correspondente no GitHub.

## Recursos principais

- Conjuntos de políticas delimitados pelo perfil ativo e pela entidade do
  chamado no GLPI.
- Precedência da entidade exata, seguida pela política recursiva do ancestral
  mais próximo.
- Restrições de abertura e atualização para 19 controles de chamado suportados.
- Controle global de tipos de ator usuário, grupo e fornecedor para cada papel.
- Aplicação opcional no backend para restrições selecionadas de abertura e
  atualização.
- Suporte a controles Select2 de chamados do GLPI, campos de data Flatpickr e
  controles de solicitação de aprovação.
- Eventos de auditoria projetados para não conter conteúdo de chamados ou dados
  pessoais.
- Textos fonte em inglês e tradução de runtime em português brasileiro.

Opening e Update tornam o controle selecionado somente leitura no formulário
de chamado aplicável. Backend torna a mesma ação restritiva nos hooks do
servidor, incluindo API e processos automáticos. JavaScript é apenas
apresentação; as ACLs do GLPI permanecem autoritativas e Torah nunca concede
um direito de acesso.

## Instalação

1. Baixe o asset de produção `torah-<version>.tar.gz` ou
   `torah-<version>.zip` da release publicada correspondente no GitHub.
2. Verifique-o com o arquivo `SHA256SUMS.txt` anexado.
3. Extraia-o diretamente em `<GLPI_ROOT>/plugins`. O diretório resultante deve
   ser `<GLPI_ROOT>/plugins/torah`.
4. Abra **Configuração > Plugins** e instale e ative o **Torah**.
5. Abra a página de configuração do plugin e crie conjuntos de políticas.

Não renomeie o diretório `torah`. Use somente os assets de pacote de produção
validados. Os arquivos gerados automaticamente pelo GitHub, “Source code
(zip)” e “Source code (tar.gz)”, são snapshots do repositório, não pacotes de
produção.

## Configuração

Os tipos de ator são uma configuração global, independente de perfis,
entidades e políticas. Eles determinam quais novos solicitantes, observadores
e técnicos podem ser adicionados na interface e no backend. Os atores
existentes nunca são removidos automaticamente; permanecem visíveis e podem
ser removidos enquanto o respectivo campo estiver editável.

Cada conjunto de políticas seleciona um perfil, uma entidade, uma opção de
recursão e as regras a bloquear. As regras nativas afetam somente chamados.
Para um chamado, Torah usa exatamente um conjunto de políticas:

1. O conjunto de políticas da entidade exata do chamado.
2. Caso contrário, o conjunto de políticas recursivo do ancestral mais próximo.
3. Caso contrário, não há interferência.

As regras de múltiplos conjuntos de políticas nunca são mescladas. Um conjunto
de políticas sem regras marcadas interrompe a herança intencionalmente e não
aplica restrições de propriedades. Para GLPI 10.0.20, Torah aplica restrições
visuais ao formulário nativo de chamado identificado por `#itil-form`.

## Atualização e desinstalação

Use a página de plugins do GLPI para executar atualizações. As migrações são
idempotentes. Desativar o plugin interrompe imediatamente a aplicação das
restrições do Torah. A desinstalação remove as tabelas próprias do Torah e não
modifica dados do GLPI core.

## Segurança e privacidade

Consulte [SECURITY.md](SECURITY.md) e [docs/PRIVACY.md](docs/PRIVACY.md). Não
abra issues públicas contendo dados reais do GLPI, credenciais, logs ou capturas
de tela com informações pessoais.

## Licença

GPL-3.0-or-later. Consulte [LICENSE](LICENSE).
