<div class="card">
    <div class="section-title">
        <h2>Gestão de tarefas</h2>
    </div>
    <div class="tabs">
        <button class="tab-botao <?php echo ($acao != 'editar') ? 'ativo' : ''; ?>" onclick="abrirTab('lista', this)">
            Listas de tarefas
        </button>
        <button class="tab-botao <?php echo ($acao == 'editar') ? 'ativo' : ''; ?>" onclick="abrirTab('formulario', this)">
           <?php echo ($acao == 'editar') ? 'Editar' : 'Nova'; ?> Tarefa
        </button>
    </div>
    
    <div class="tab-conteudo <?php echo ($acao == 'editar') ? 'ativo' : ''; ?>" id="formulario">
        <h2><?php echo ($acao == 'editar') ? 'Editar Tarefa' : 'Nova Tarefa'; ?></h2>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            
            <?php if ($acao == 'editar' && $registro): ?>
                <input type="hidden" name="id" value="<?php echo e($registro['id_tarefas']); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" required><?php echo ($registro) ? e($registro['descricao']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="qtdpontos">Quantidade de Pontos:</label>
                <input type="number" id="qtdpontos" name="qtdpontos" min="1" required value="<?php echo ($registro) ? e($registro['qtd_pon_ganho']) : ''; ?>">
            </div>
            
            <div class="botoes">
                <?php if ($acao == 'editar'): ?>
                    <button type="submit" name="atualizar" class="btn-primario">Atualizar</button>
                    <a href="painel.php?secao=tarefas" class="btn-secundario">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="criar" class="btn-primario">Criar</button>
                    <button type="reset" class="btn-secundario">Limpar</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="tab-conteudo <?php echo ($acao != 'editar') ? 'ativo' : ''; ?>" id="lista">
        <h2>Tarefas padrão do sistema</h2>
        
        <?php if (count($todos) > 0): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>ID</th>
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
                            <td><?php echo e($item['id_tarefas']); ?></td>
                            <td><?php echo e(substr($item['descricao'], 0, 50)); ?></td>
                            <td><strong><?php echo e($item['qtd_pon_ganho']); ?></strong></td>
                            <td><?php echo e(ucfirst($item['status'] ?? 'Pendente')); ?></td>
                            <td><?php echo e(date('d/m/Y', strtotime($item['data_criacao']))); ?></td>
                            <td>
                                <div class="acoes">
                                    <a href="painel.php?secao=tarefas&acao=editar&id=<?php echo e($item['id_tarefas']); ?>" class="btn-editar">Editar</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="id" value="<?php echo e($item['id_tarefas']); ?>">
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
                <p>Nenhuma tarefa cadastrada. <a href="painel.php?secao=tarefas">Crie uma nova!</a></p>
            </div>
        <?php endif; ?>

        <h2>Tarefas cadastradas pelos usuários (<?php echo count($tarefasUsuarios); ?>)</h2>
        <?php if (count($tarefasUsuarios) > 0): ?>
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Pontos</th>
                        <th>Status</th>
                        <th>Criação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tarefasUsuarios as $item): ?>
                        <tr>
                            <td><?php echo e($item['nome_completo'] ?: $item['email'] ?: 'Usuário desconhecido'); ?></td>
                            <td><?php echo e(substr($item['descricao'], 0, 50)); ?></td>
                            <td><?php echo e(ucfirst($item['tipo'])); ?></td>
                            <td><strong><?php echo e($item['qtd_pon_ganho']); ?></strong></td>
                            <td><?php echo e(ucfirst($item['status'])); ?></td>
                            <td><?php echo e(date('d/m/Y', strtotime($item['data_criacao']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="vazio">
                <p>Nenhuma tarefa cadastrada pelos usuários.</p>
            </div>
        <?php endif; ?>
    </div>
</div>