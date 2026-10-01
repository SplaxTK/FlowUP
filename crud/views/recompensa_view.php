<div class="card">
    <div class="section-title">
        <h2>Gestão de recompensas</h2>
    </div>
    <div class="tabs">
        <button class="tab-botao <?php echo ($acao != 'editar') ? 'ativo' : ''; ?>" onclick="abrirTab('lista', this)">
            Listar Todas (<?php echo count($todos); ?>)
        </button>
        <button class="tab-botao <?php echo ($acao == 'editar') ? 'ativo' : ''; ?>" onclick="abrirTab('formulario', this)">
            <?php echo ($acao == 'editar') ? 'Editar' : 'Nova'; ?> Recompensa
        </button>
    </div>
    
    <div class="tab-conteudo <?php echo ($acao == 'editar') ? 'ativo' : ''; ?>" id="formulario">
        <h2><?php echo ($acao == 'editar') ? 'Editar Recompensa' : 'Nova Recompensa'; ?></h2>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            
            <?php if ($acao == 'editar' && $registro): ?>
                <input type="hidden" name="id" value="<?php echo e($registro['id_recompensa']); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required><?php echo ($registro) ? e($registro['descricao']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="qtdpontos">Pontos Necessários:</label>
                <input type="number" id="qtdpontos" name="qtdpontos" min="1" required value="<?php echo ($registro) ? e($registro['qtd_pon_neces']) : ''; ?>">
            </div>
            
            <div class="botoes">
                <?php if ($acao == 'editar'): ?>
                    <button type="submit" name="atualizar" class="btn-primario">Atualizar</button>
                    <a href="index.php?secao=recompensa" class="btn-secundario">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="criar" class="btn-primario">Criar</button>
                    <button type="reset" class="btn-secundario">Limpar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="tab-conteudo <?php echo ($acao != 'editar') ? 'ativo' : ''; ?>" id="lista">
        <h2>Recompensas</h2>
        
        <?php if (count($todos) > 0): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Descrição</th>
                        <th>Pontos</th>
                        <th>Status</th>
                        <th>Criação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todos as $item): ?>
                        <tr>
                            <td><?php echo e($item['id_recompensa']); ?></td>
                            <td><?php echo e(substr($item['descricao'], 0, 50)); ?></td>
                            <td><strong><?php echo e($item['qtd_pon_neces']); ?></strong></td>
                            <td><?php echo e(ucfirst($item['status'] ?? 'Disponível')); ?></td>
                            <td><?php echo e(date('d/m/Y', strtotime($item['data_criacao']))); ?></td>
                            <td>
                                <div class="acoes">
                                    <a href="index.php?secao=recompensa&acao=editar&id=<?php echo e($item['id_recompensa']); ?>" class="btn-editar">Editar</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="id" value="<?php echo e($item['id_recompensa']); ?>">
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
                <p>Nenhuma recompensa cadastrada. <a href="index.php?secao=recompensa">Crie uma nova!</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>