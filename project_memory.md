# Project Memory - News-Caster Studio (NCS)

## 📌 Status Atual & Últimas Ações (v1.1 - 22/Set/2026)
- **Sincronização Dinâmica de Categorias & Radar de Pautas:**
  - O seletor do Radar de Pautas (`#radar-topic-select`) é alimentado dinamicamente pelas categorias reais do portal WordPress (`api/categories.php`).
  - **Auto-Fill de Categoria:** Ao selecionar um tópico para pesquisar no Radar, a categoria de publicação no WordPress é sincronizada e preenchida automaticamente.
- **Suporte a Imagem de Destaque (Featured Image):**
  - `api/generate.php`: Extrai automaticamente tags `og:image` ou `twitter:image` do link da matéria original.
  - `api/publish.php`: Faz o upload automático da imagem via REST API (`/wp-json/wp/v2/media`) e vincula o ID como `featured_media` no post.
- **Preview Social (Instagram):**
  - Exibe a imagem real da matéria no card do Instagram ou gera dinamicamente a arte vetorial 1:1 (SVG) com o logotipo do *Notícia Baré*.
- **Deploy & Versioneamento:**
  - Script de deploy FTP: `deploy-production.ps1` -> `ftp://noticiabare.com/studio/`.
  - Repositório GitHub: `https://github.com/Mariozinhocs/News-Caster-Studio.git` (Branch `main`).

## 🏗️ Estrutura do News-Caster Studio
* **Frontend:**
  * `index.html` (Interface principal do estúdio)
  * `assets/css/style.css` (Estilos principais)
  * `assets/js/app.js` (Controlador da UI, sincronização e preview)
* **Backend (APIs PHP):**
  * `api/generate.php` (Geração de pautas/notícias e extração de metadados/imagens)
  * `api/publish.php` (Publicação REST API com upload de mídia e categorização no WP)
  * `api/trends.php` (Consulta de trending topics dinâmicos por categoria do portal)
  * `api/categories.php` (Sincronização de categorias com a REST API do WordPress)

## 🔗 Links Úteis
- **Repositório Oficial (GitHub):** [https://github.com/Mariozinhocs/News-Caster-Studio.git](https://github.com/Mariozinhocs/News-Caster-Studio.git)
- **Produção (Web):** [https://noticiabare.com/studio/](https://noticiabare.com/studio/)

---
*Nota: Este projeto funciona como um Copiloto Jornalista para a redação do Notícia Baré.*
