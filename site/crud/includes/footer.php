</div>
    <script>
        function abrirTab(tab, botao) {
            document.querySelectorAll('.tab-conteudo').forEach(e => e.classList.remove('ativo'));
            document.querySelectorAll('.tab-botao').forEach(e => e.classList.remove('ativo'));
            document.getElementById(tab).classList.add('ativo');
            botao.classList.add('ativo');
        }
    </script>
</body>
</html>