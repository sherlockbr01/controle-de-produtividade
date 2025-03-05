#!/bin/bash
echo "Verificando o status do repositório..."
git status

echo "Adicionando todas as alterações..."
git add .

echo "Fazendo commit das alterações..."
git commit -m "Atualização do sistema de produtividade: adicionado gerenciamento de grupos e outras melhorias"

echo "Puxando alterações do repositório remoto..."
git pull origin main

echo "Enviando alterações para o repositório remoto..."
git push origin main

echo "Processo concluído!"