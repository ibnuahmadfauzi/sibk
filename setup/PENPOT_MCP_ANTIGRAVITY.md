# Penpot MCP → Google Antigravity

## 1. Buat MCP Key Penpot
1. Masuk ke Penpot.
2. Buka `Your account → Integrations → MCP Server`.
3. Enable MCP Server.
4. Generate MCP Key.
5. Simpan key pribadi.
6. Salin server URL.

Contoh:
`https://design.penpot.app/mcp/stream?userToken=YOUR_MCP_KEY`

Jangan bagikan key dan jangan commit ke Git.

## 2. Tambahkan ke Antigravity
1. Buka Antigravity.
2. `... → MCP Servers → Manage MCP Servers → View raw config`.
3. Tambahkan:

```json
{
  "mcpServers": {
    "penpot": {
      "serverUrl": "https://design.penpot.app/mcp/stream?userToken=YOUR_MCP_KEY"
    }
  }
}
```

## 3. Hubungkan Penpot
1. Buka file Ruang BK.
2. Fokus ke `22 — UI High-Fidelity Final` atau `22.5 — Style Guide`.
3. `File → MCP Server → Connect`.

## 4. Uji Read-only
`Gunakan Penpot MCP secara read-only. Sebutkan page aktif dan board PG yang terlihat. Jangan mengubah desain.`

## 5. Workflow
Frontend:
`/implement-page PG-103 Detail Kasus`

Verifikasi:
`/verify-page PG-103`

Perencanaan backend:
`/plan-backend Layanan BK`

`/plan-backend` tidak boleh mengimplementasikan backend.
