# ConversationMedia

Foto privada de uma conversa de Sessão Esportiva. Pertence à conversa e ao Perfil Esportivo autor; `event_message_media` só a associa a uma mensagem depois da aprovação.

- Estados: `processing`, `approved`, `rejected`.
- O original fica em `upload_key` privado e é removido após processamento; `safe_key` e `thumbnail_key` são variantes sem metadados.
- A leitura passa novamente pela autorização da conversa e usa URL curta.
