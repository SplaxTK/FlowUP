<?php
// Valores padrão para evitar avisos de variáveis não definidas.
$acao = $acao ?? '';
$todos = $todos ?? [];
$manter_form = $manter_form ?? false;
$registro = $registro ?? null;
$busca = $busca ?? '';
$erro_email = $erro_email ?? '';
?>

<div class="card">
    <div class="section-title">
        <h2>Gestão de cadastros</h2>
    </div>

    <div class="search-actions">
        <form method="get" class="search-form">
            <input type="hidden" name="secao" value="cadastro">
            <input type="text" name="busca" placeholder="Buscar email ou nome" value="<?php echo e($busca); ?>">
            <button type="submit" class="btn-secundario">Buscar</button>
        </form>
    </div>

    <div class="tabs">
        <button class="tab-botao <?php echo ($acao != 'editar' && !$manter_form) ? 'ativo' : ''; ?>" onclick="abrirTab('lista', this)">
            Listar Todos (<?php echo count($todos); ?>)
        </button>
        <button class="tab-botao <?php echo ($acao == 'editar' || $manter_form) ? 'ativo' : ''; ?>" onclick="abrirTab('formulario', this)">
            <?php echo ($acao == 'editar') ? 'Editar' : 'Novo'; ?> Cadastro
        </button>
    </div>
    
    <div class="tab-conteudo <?php echo ($acao == 'editar' || $manter_form) ? 'ativo' : ''; ?>" id="formulario">
        <h2><?php echo ($acao == 'editar') ? 'Editar Cadastro' : 'Novo Cadastro'; ?></h2>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="email">Email: <?php echo ($acao == 'editar') ? '(chave primária)' : ''; ?></label>
                <input type="email" id="email" name="email" required <?php echo ($acao == 'editar') ? 'readonly' : ''; ?> value="<?php echo ($registro) ? e($registro['email']) : ''; ?>">
                <?php if (!empty($erro_email)): ?>
                    <div class="field-error"><?php echo e($erro_email); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="usuario">Usuário:</label>
                <input type="text" id="usuario" name="usuario" required value="<?php echo ($registro) ? e($registro['usuario']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="nome_completo">Nome completo:</label>
                <input type="text" id="nome_completo" name="nome_completo" required value="<?php echo ($registro) ? e($registro['nome_completo']) : ''; ?>">
            </div>
            
            <?php if ($acao == 'editar'): ?>
                <p>A senha não pode ser alterada pelo painel administrativo.</p>
            <?php else: ?>
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required value="">
                </div>
            <?php endif; ?>
            
            <div class="botoes">
                <?php if ($acao == 'editar'): ?>
                    <button type="submit" name="atualizar" class="btn-primario">Atualizar</button>
                    <a href="painel.php?secao=cadastro" class="btn-secundario">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="criar" class="btn-primario">Criar</button>
                    <button type="reset" class="btn-secundario">Limpar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="tab-conteudo <?php echo ($acao != 'editar' && !$manter_form) ? 'ativo' : ''; ?>" id="lista">
        <h2>Cadastros</h2>
        
        <?php if (count($todos) > 0): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Usuário</th>
                        <th>Data Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todos as $item): ?>
                        <tr>
                            <td><?php echo e($item['email']); ?></td>
                            <td><?php echo e($item['usuario']); ?></td>
                            <td><?php echo e(date('d/m/Y H:i', strtotime($item['criado_em']))); ?></td>
                            <td>
                                <div class="acoes">
                                    <a href="painel.php?secao=cadastro&acao=editar&email=<?php echo urlencode($item['email']); ?>" class="btn-editar">Editar</a>
                                        <form method="POST" class="form-excluir-cadastro" style="display:inline;">                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="email" value="<?php echo e($item['email']); ?>">
                                        <button type="submit" name="deletar" class="btn-deletar">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="vazio">
                <p>Nenhum cadastro encontrado. <a href="painel.php?secao=cadastro">Crie um novo!</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<style>
body.modal-exclusao-aberto {
    overflow: hidden;
}

.modal-exclusao {
    display: none !important;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-exclusao.aberto {
    display: flex !important;
}

.modal-exclusao-fundo {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
}

.modal-exclusao-conteudo {
    position: relative;
    width: min(100%, 420px);
    padding: 32px 28px 24px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25);
    text-align: center;
    outline: none;
}

.modal-exclusao-icone {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    border-radius: 14px;
    background: var(--error-bg);
    color: var(--error);
    font-size: 24px;
    font-weight: 700;
}

.modal-exclusao-conteudo h2 {
    margin-bottom: 10px;
    color: var(--text);
    font-size: 1.25rem;
}

.modal-exclusao-conteudo p {
    color: var(--muted);
    line-height: 1.5;
}

.modal-exclusao-acoes {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 24px;
}

.modal-btn-cancelar,
.modal-btn-confirmar {
    padding: 11px 20px;
    border-radius: 9px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
}

.modal-btn-cancelar {
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--muted);
}

.modal-btn-cancelar:hover {
    background: #f1f5f9;
}

.modal-btn-confirmar {
    background: var(--error);
    border: 1px solid var(--error);
    color: #fff;
}

.modal-btn-confirmar:hover {
    background: #b91c1c;
}
</style>

<div class="modal-exclusao" id="modalExclusao" aria-hidden="true" style="display:none;">
    <div class="modal-exclusao-fundo" data-fechar-modal></div>

    <section
        class="modal-exclusao-conteudo"
        role="dialog"
        aria-modal="true"
        aria-labelledby="tituloExclusao"
        aria-describedby="descricaoExclusao"
        tabindex="-1"
    >
        <div class="modal-exclusao-icone" aria-hidden="true">!</div>

        <h2 id="tituloExclusao">Excluir cadastro?</h2>

        <p id="descricaoExclusao">
            Tem certeza de que deseja excluir esta conta?
            Essa ação não poderá ser desfeita.
        </p>

        <div class="modal-exclusao-acoes">
            <button type="button" class="modal-btn-cancelar" data-fechar-modal>
                Cancelar
            </button>

            <button type="button" class="modal-btn-confirmar" id="confirmarExclusao">
                Excluir conta
            </button>
        </div>
    </section>
</div>

<script>
(function () {
    function inicializarModalExclusao() {
        const modal = document.getElementById('modalExclusao');

        if (!modal || modal.dataset.inicializado === 'true') {
            return;
        }

        modal.dataset.inicializado = 'true';

        const botaoConfirmar = modal.querySelector('#confirmarExclusao');

        if (!botaoConfirmar) {
            return;
        }

        let formularioSelecionado = null;
        let botaoAnterior = null;

        function abrirModal(formulario, botao) {
            formularioSelecionado = formulario;
            botaoAnterior = botao;

            modal.classList.add('aberto');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-exclusao-aberto');

            botaoConfirmar.focus();
        }

        function fecharModal() {
            modal.classList.remove('aberto');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-exclusao-aberto');

            const focoAnterior = botaoAnterior;

            formularioSelecionado = null;
            botaoAnterior = null;

            if (focoAnterior && document.contains(focoAnterior)) {
                focoAnterior.focus();
            }
        }

        document.querySelectorAll('form.form-excluir-cadastro').forEach(function (formulario) {
            formulario.addEventListener('submit', function (evento) {
                if (formulario.dataset.exclusaoConfirmada === 'true') {
                    delete formulario.dataset.exclusaoConfirmada;
                    return;
                }

                evento.preventDefault();

                const botao = evento.submitter ||
                    formulario.querySelector('button[name="deletar"]');

                abrirModal(formulario, botao);
            });
        });

        botaoConfirmar.addEventListener('click', function () {
            if (!formularioSelecionado) {
                return;
            }

            const formulario = formularioSelecionado;
            const botaoEnvio = botaoAnterior;

            formularioSelecionado = null;
            botaoAnterior = null;

            modal.classList.remove('aberto');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-exclusao-aberto');

            formulario.dataset.exclusaoConfirmada = 'true';

            if (botaoEnvio && formulario.contains(botaoEnvio)) {
                formulario.requestSubmit(botaoEnvio);
            } else {
                formulario.requestSubmit();
            }
        });

        modal.querySelectorAll('[data-fechar-modal]').forEach(function (elemento) {
            elemento.addEventListener('click', fecharModal);
        });

        document.addEventListener('keydown', function (evento) {
            if (!modal.classList.contains('aberto')) {
                return;
            }

            if (evento.key === 'Escape') {
                evento.preventDefault();
                fecharModal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarModalExclusao, {
            once: true
        });
    } else {
        inicializarModalExclusao();
    }
})();
</script>