# Registro de alterações

Esta é a tradução em português brasileiro das alterações voltadas ao usuário.
O [CHANGELOG.md](CHANGELOG.md) em inglês é a fonte autoritativa.

## [Unreleased]

## [0.4.15] - 2026-08-21

### Alterado

- Preparados os metadados da primeira release pública estável com sua URL
  determinística do pacote de produção, documentação de compatibilidade atual e
  os 26 controles de chamado efetivamente suportados.
- Removidos metadados históricos não publicados que não possuem asset de
  produção publicamente verificável.

## [0.4.9] - 2026-08-13

### Adicionado

- Adicionado o logo de catálogo PNG de 40×40 e sua URL pública raw do
  repositório aos metadados do plugin e ao pacote de produção validado.
- Adicionado gerador determinístico de notas de release a partir da seção
  correspondente do changelog e de assets de produção validados.
- Adicionada cobertura de regressão para extração de notas de release e casos
  de validação de URL de pacote do catálogo.
- Adicionado README voltado ao usuário em português brasileiro.

### Alterado

- Mantido o README em inglês focado no uso, instalação, configuração,
  segurança, compatibilidade e pacotes oficiais de produção do Torah.
- Preservado 0.4.8 como marco interno e imutável de desenvolvimento, sem
  release no GitHub, e identificadas as entradas anteriores do changelog como
  marcos de desenvolvimento, não como releases publicadas.
- Preparado o 0.4.9 como a primeira release pública estável, com somente assets
  de pacote de produção validados e checksums.

### Corrigido

- Impedida a criação de draft releases com notas estáticas não relacionadas à
  seção correspondente do changelog.
- Removidos links do changelog para releases e tags inexistentes.
- Removidas as configurações globais pertencentes ao Torah durante a
  desinstalação do plugin.
