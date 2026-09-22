document.addEventListener('DOMContentLoaded', () => {
    // Elementos do DOM
    const articleTitleInput = document.getElementById('article-title');
    const articleBodyTextarea = document.getElementById('article-body');
    const toolbar = document.getElementById('editor-toolbar');
    const trendingList = document.getElementById('trending-list');
    const btnRefreshRadar = document.getElementById('btn-refresh-radar');
    const btnGenerateAI = document.getElementById('btn-generate-ai');
    
    // Social Preview
    const igCaptionText = document.getElementById('ig-caption-text');
    const igTagsText = document.getElementById('ig-tags-text');
    const btnRegenCaption = document.getElementById('btn-regen-caption');

    // Publicação
    const btnPublish = document.getElementById('btn-publish');
    const statusSelect = document.getElementById('post-status-select');
    const categorySelect = document.getElementById('post-category-select');
    const statusMessage = document.getElementById('status-message');
    const destNoticiaBare = document.getElementById('dest-noticiabare');

    // -------------------------------------------------------------
    // 1. Formatação no Editor de Texto
    // -------------------------------------------------------------
    if (toolbar) {
        toolbar.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn) return;
            e.preventDefault();

            const format = btn.getAttribute('data-format');
            if (format) {
                applyFormat(format);
            }
        });
    }

    function applyFormat(format) {
        const start = articleBodyTextarea.selectionStart;
        const end = articleBodyTextarea.selectionEnd;
        const text = articleBodyTextarea.value;
        const selected = text.substring(start, end);

        let openTag = '';
        let closeTag = '';

        switch (format) {
            case 'bold': openTag = '<b>'; closeTag = '</b>'; break;
            case 'italic': openTag = '<i>'; closeTag = '</i>'; break;
            case 'h2': openTag = '<h2>'; closeTag = '</h2>'; break;
            case 'h3': openTag = '<h3>'; closeTag = '</h3>'; break;
            case 'link':
                const url = prompt('Digite a URL do link:', 'https://');
                if (!url) return;
                openTag = `<a href="${url}">`;
                closeTag = '</a>';
                break;
        }

        const formatted = openTag + (selected || 'seu texto aqui') + closeTag;
        articleBodyTextarea.value = text.substring(0, start) + formatted + text.substring(end);
        
        // Ajustar cursor
        const newCursorPos = start + openTag.length + (selected ? selected.length : 14);
        articleBodyTextarea.focus();
        articleBodyTextarea.setSelectionRange(newCursorPos, newCursorPos);

        updateSocialPreview();
    }

    // -------------------------------------------------------------
    // 2. Sincronização do Preview do Instagram em Tempo Real
    // -------------------------------------------------------------
    function updateSocialPreview() {
        const title = articleTitleInput.value.trim();
        const body = articleBodyTextarea.value.trim();

        if (!title && !body) {
            igCaptionText.innerHTML = '<strong>noticiabare</strong> Digite um título ou matéria para visualizar a legenda aqui.';
            return;
        }

        // Pega os primeiros parágrafos sem tags HTML para o preview
        const cleanBody = body.replace(/<[^>]*>?/gm, '');
        const paragraphs = cleanBody.split('\n').filter(p => p.trim() !== '');
        const firstParagraph = paragraphs.length > 0 ? paragraphs[0] : '';

        // Formata uma legenda engajadora
        igCaptionText.innerHTML = `<strong>noticiabare</strong> 📢 <strong>${title}</strong><br><br>${firstParagraph.substring(0, 180)}${firstParagraph.length > 180 ? '...' : ''} 🗞️<br><br>👉 Confira a matéria completa no link da bio!`;

        // Gerar Hashtags automáticas baseadas no título
        const words = title.split(' ')
            .map(w => w.replace(/[^a-zA-Z0-9À-ÿ]/g, ''))
            .filter(w => w.length > 4);
        
        const uniqueHashtags = Array.from(new Set(words.map(w => '#' + w))).slice(0, 5);
        if (!uniqueHashtags.includes('#NoticiaBare')) uniqueHashtags.push('#NoticiaBare');
        if (!uniqueHashtags.includes('#Jornalismo')) uniqueHashtags.push('#Jornalismo');

        igTagsText.textContent = uniqueHashtags.join(' ');
    }

    // -------------------------------------------------------------
    // 2. Sincronização do Preview do Instagram & Gerador Visual de Capa
    // -------------------------------------------------------------
    const igImagePreview = document.getElementById('ig-image-preview');

    function renderInstagramCoverImage(titleText) {
        if (!igImagePreview) return;
        
        const displayTitle = titleText || 'Notícia Baré';
        const truncatedTitle = displayTitle.length > 70 ? displayTitle.substring(0, 67) + '...' : displayTitle;

        // Criar SVG dinâmico em alta qualidade 1:1
        const svgCard = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500" width="100%" height="100%">
            <defs>
                <linearGradient id="bg-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#0F172A" />
                    <stop offset="50%" stop-color="#1E1B4B" />
                    <stop offset="100%" stop-color="#311042" />
                </linearGradient>
                <linearGradient id="badge-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#8B5CF6" />
                    <stop offset="100%" stop-color="#3B82F6" />
                </linearGradient>
            </defs>
            <rect width="500" height="500" fill="url(#bg-grad)"/>
            <circle cx="420" cy="80" r="140" fill="#8B5CF6" opacity="0.15"/>
            <circle cx="80" cy="420" r="160" fill="#3B82F6" opacity="0.15"/>
            
            <!-- Badge Logo -->
            <rect x="40" y="40" width="160" height="34" rx="8" fill="url(#badge-grad)"/>
            <text x="120" y="62" fill="#FFFFFF" font-family="Inter, sans-serif" font-size="14" font-weight="800" text-anchor="middle" letter-spacing="1">NOTÍCIA BARÉ</text>
            
            <!-- Linha Decorativa -->
            <line x1="40" y1="100" x2="460" y2="100" stroke="rgba(255,255,255,0.15)" stroke-width="2"/>
            
            <!-- Título da Matéria -->
            <foreignObject x="40" y="140" width="420" height="260">
                <div xmlns="http://www.w3.org/1999/xhtml" style="color:#FFFFFF; font-family:'Inter', sans-serif; font-size:26px; font-weight:800; line-height:1.35; word-wrap:break-word;">
                    ${truncatedTitle}
                </div>
            </foreignObject>
            
            <!-- Rodapé Card -->
            <rect x="40" y="420" width="420" height="40" rx="6" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.1)"/>
            <text x="250" y="445" fill="#9CA3AF" font-family="Inter, sans-serif" font-size="13" font-weight="600" text-anchor="middle">noticiabare.com • Leia a matéria completa</text>
        </svg>`;

        igImagePreview.innerHTML = svgCard;
    }

    function updateSocialPreview() {
        const title = articleTitleInput.value.trim();
        const body = articleBodyTextarea.value.trim();

        renderInstagramCoverImage(title);

        if (!title && !body) {
            igCaptionText.innerHTML = '<strong>noticiabare</strong> Digite um título ou matéria para visualizar a legenda aqui.';
            return;
        }

        const cleanBody = body.replace(/<[^>]*>?/gm, '');
        const paragraphs = cleanBody.split('\n').filter(p => p.trim() !== '');
        const firstParagraph = paragraphs.length > 0 ? paragraphs[0] : '';

        igCaptionText.innerHTML = `<strong>noticiabare</strong> 📢 <strong>${title}</strong><br><br>${firstParagraph.substring(0, 180)}${firstParagraph.length > 180 ? '...' : ''} 🗞️<br><br>👉 Confira a matéria completa no link da bio!`;

        const words = title.split(' ')
            .map(w => w.replace(/[^a-zA-Z0-9À-ÿ]/g, ''))
            .filter(w => w.length > 4);
        
        const uniqueHashtags = Array.from(new Set(words.map(w => '#' + w))).slice(0, 5);
        if (!uniqueHashtags.includes('#NoticiaBare')) uniqueHashtags.push('#NoticiaBare');
        if (!uniqueHashtags.includes('#Jornalismo')) uniqueHashtags.push('#Jornalismo');

        igTagsText.textContent = uniqueHashtags.join(' ');
    }

    // Ouvidores de eventos para atualização em tempo real
    articleTitleInput.addEventListener('input', updateSocialPreview);
    articleBodyTextarea.addEventListener('input', updateSocialPreview);

    if (btnRegenCaption) {
        btnRegenCaption.addEventListener('click', () => {
            updateSocialPreview();
            showStatus('Capa e Legenda do Instagram atualizadas!', 'success', 2000);
        });
    }

    // -------------------------------------------------------------
    // 3. Radar de Pautas com Feeds RSS em Tempo Real
    // -------------------------------------------------------------
    async function loadRssRadarTrends() {
        if (!trendingList) return;

        if (btnRefreshRadar) btnRefreshRadar.textContent = '🔄 Buscando Feeds RSS...';

        try {
            const response = await fetch('api/trends.php');
            const data = await response.json();

            if (data.success && data.trends && data.trends.length > 0) {
                trendingList.innerHTML = '';
                
                data.trends.forEach((item, index) => {
                    const li = document.createElement('li');
                    li.className = `trending-item ${index === 0 ? 'active' : ''}`;
                    li.setAttribute('data-title', item.title);
                    li.setAttribute('data-body', item.description);
                    li.setAttribute('data-link', item.link);

                    li.innerHTML = `
                        <span class="rank">${item.rank}</span>
                        <div class="topic-info">
                            <h4>${item.title}</h4>
                            <p>${item.category} • ${item.source}</p>
                        </div>
                    `;
                    trendingList.appendChild(li);
                });

                showStatus('📡 Radar atualizado com notícias reais do RSS!', 'success', 3000);
            } else {
                showStatus('Não foi possível atualizar o radar via RSS.', 'error', 3000);
            }
        } catch (err) {
            console.error('Erro ao carregar RSS Trends:', err);
            showStatus('Erro de conexão ao buscar Feeds RSS.', 'error', 3000);
        } finally {
            if (btnRefreshRadar) btnRefreshRadar.textContent = 'Atualizar Radar (RSS)';
        }
    }

    if (trendingList) {
        trendingList.addEventListener('click', (e) => {
            const item = e.target.closest('.trending-item');
            if (!item) return;

            document.querySelectorAll('.trending-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');

            const title = item.getAttribute('data-title');
            const body = item.getAttribute('data-body');
            const link = item.getAttribute('data-link');

            if (title) articleTitleInput.value = title;
            if (body) articleBodyTextarea.value = body;
            if (link && aiUrlInput) aiUrlInput.value = link;

            updateSocialPreview();
        });
    }

    if (btnRefreshRadar) {
        btnRefreshRadar.addEventListener('click', loadRssRadarTrends);
    }


    // -------------------------------------------------------------
    // 4. Modal & Gerador de Notícias com IA
    // -------------------------------------------------------------
    const aiModalOverlay = document.getElementById('ai-modal-overlay');
    const btnCloseAiModal = document.getElementById('btn-close-ai-modal');
    const btnCancelAiModal = document.getElementById('btn-cancel-ai-modal');
    const btnSubmitAi = document.getElementById('btn-submit-ai');
    const aiUrlInput = document.getElementById('ai-url-input');
    const aiPromptInput = document.getElementById('ai-prompt-input');
    const aiToneSelect = document.getElementById('ai-tone-select');

    if (btnGenerateAI && aiModalOverlay) {
        btnGenerateAI.addEventListener('click', () => {
            const currentTitle = articleTitleInput.value.trim();
            if (currentTitle && !aiPromptInput.value) {
                aiPromptInput.value = currentTitle;
            }
            aiModalOverlay.classList.add('active');
        });
    }

    function closeAiModal() {
        if (aiModalOverlay) aiModalOverlay.classList.remove('active');
    }

    if (btnCloseAiModal) btnCloseAiModal.addEventListener('click', closeAiModal);
    if (btnCancelAiModal) btnCancelAiModal.addEventListener('click', closeAiModal);

    if (aiModalOverlay) {
        aiModalOverlay.addEventListener('click', (e) => {
            if (e.target === aiModalOverlay) closeAiModal();
        });
    }

    if (btnSubmitAi) {
        btnSubmitAi.addEventListener('click', async () => {
            const url = aiUrlInput ? aiUrlInput.value.trim() : '';
            const prompt = aiPromptInput ? aiPromptInput.value.trim() : '';
            const tone = aiToneSelect ? aiToneSelect.value : 'jornalistico';

            if (!url && !prompt) {
                alert('Informe um link de notícia ou descreva o tema/esboço da pauta.');
                return;
            }

            btnSubmitAi.disabled = true;
            btnSubmitAi.textContent = '✨ Gerando Matéria...';

            try {
                const response = await fetch('api/generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt, url, tone })
                });

                const data = await response.json();

                if (data.success) {
                    if (data.title) articleTitleInput.value = data.title;
                    if (data.content) articleBodyTextarea.value = data.content;
                    
                    if (data.instagram_caption) {
                        igCaptionText.innerHTML = data.instagram_caption.replace(/\n/g, '<br>');
                    }
                    if (data.hashtags) {
                        igTagsText.textContent = data.hashtags;
                    }

                    closeAiModal();
                    showStatus('✨ Matéria gerada com sucesso pela IA! Pronta para revisão.', 'success', 4000);
                } else {
                    alert('Erro ao gerar matéria: ' + (data.message || 'Erro desconhecido.'));
                }
            } catch (err) {
                console.error('Erro ao conectar com gerador de IA:', err);
                alert('Erro de conexão ao gerar matéria.');
            } finally {
                btnSubmitAi.disabled = false;
                btnSubmitAi.textContent = '✨ Gerar Matéria Completa';
            }
        });
    }


    // -------------------------------------------------------------
    // 5. Envio & Publicação para WordPress via API
    // -------------------------------------------------------------
    if (btnPublish) {
        btnPublish.addEventListener('click', async () => {
            const title = articleTitleInput.value.trim();
            const content = articleBodyTextarea.value.trim();
            const status = statusSelect ? statusSelect.value : 'draft';
            const category = categorySelect ? categorySelect.value : 0;

            if (!destNoticiaBare || !destNoticiaBare.checked) {
                showStatus('Selecione pelo menos um destino para publicação (Notícia Baré).', 'error');
                return;
            }

            if (!title || !content) {
                showStatus('O título e o conteúdo da matéria não podem estar vazios.', 'error');
                return;
            }

            showStatus('🚀 Enviando matéria para o Notícia Baré...', 'loading');
            btnPublish.disabled = true;

            try {
                const response = await fetch('api/publish.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title, content, status, category })
                });

                const data = await response.json();

                if (data.success) {
                    const statusLabel = status === 'publish' ? 'Publicado!' : 'Salvo como Rascunho!';
                    let msg = `✅ ${statusLabel} (ID: ${data.post_id})`;
                    if (data.link) {
                        msg += ` - <a href="${data.link}" target="_blank" style="color:#60A5FA; text-decoration:underline;">Ver Matéria</a>`;
                    }
                    showStatus(msg, 'success');
                } else {
                    showStatus(`❌ Erro ao publicar: ${data.message || 'Erro desconhecido.'}`, 'error');
                }
            } catch (err) {
                console.error('Erro na requisição de publicação:', err);
                showStatus('❌ Erro de conexão com a API de publicação.', 'error');
            } finally {
                btnPublish.disabled = false;
            }
        });
    }

    // Helper de mensagens de status
    function showStatus(message, type = 'info', autoHideMs = 0) {
        if (!statusMessage) return;
        statusMessage.className = `status-message ${type}`;
        statusMessage.innerHTML = message;

        if (autoHideMs > 0) {
            setTimeout(() => {
                statusMessage.className = 'status-message';
                statusMessage.style.display = 'none';
            }, autoHideMs);
        }
    }

    async function loadCategories() {
        if (!categorySelect) return;
        try {
            const response = await fetch('api/categories.php');
            const data = await response.json();
            
            if (data.success && data.categories) {
                categorySelect.innerHTML = '<option value="0">Sem categoria / Padrão</option>';
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    categorySelect.appendChild(option);
                });
            } else {
                categorySelect.innerHTML = '<option value="0">Erro ao carregar categorias</option>';
            }
        } catch (err) {
            console.error('Erro ao buscar categorias:', err);
            categorySelect.innerHTML = '<option value="0">Erro ao carregar categorias</option>';
        }
    }

    // Inicializar social preview com conteúdo padrão
    updateSocialPreview();

    // Carregar radar de pautas automaticamente na inicialização (Push dos RSS disponíveis)
    loadRssRadarTrends();

    // Carregar as categorias do portal
    loadCategories();
});
