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
            
            <div class="form-group">
                <label for="senha">Senha: <?php echo ($acao == 'editar') ? '(Preencha apenas para alterar)' : ''; ?></label>
                <input type="password" id="senha" name="senha" <?php echo ($acao == 'editar') ? '' : 'required'; ?> value="">
            </div>
            
            <div class="botoes">
                <?php if ($acao == 'editar'): ?>
                    <button type="submit" name="atualizar" class="btn-primario">Atualizar</button>
                    <a href="index.php?secao=cadastro" class="btn-secundario">Cancelar</a>
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
                                    <a href="index.php?secao=cadastro&acao=editar&email=<?php echo urlencode($item['email']); ?>" class="btn-editar">Editar</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
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
                <p>Nenhum cadastro encontrado. <a href="index.php?secao=cadastro">Crie um novo!</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>