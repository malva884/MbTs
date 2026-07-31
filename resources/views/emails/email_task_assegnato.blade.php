<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo Task Assegnato</title>
    <style>
        body{margin:0;padding:0;background:#f4f4f8;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#333}
        .wrap{width:100%;max-width:720px;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)}
        .header{background:#6c2bd9;padding:16px 24px}
        .logo{height:28px}
        .content{padding:20px 24px}
        h1{margin:0 0 6px;font-size:20px;color:#1a1a2e}
        .meta{color:#8b8b9e;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin:0 0 12px}
        .task-info{background:#f8f9fa;border-left:4px solid #0d6efd;padding:12px 16px;margin:16px 0;border-radius:4px}
        .task-info-row{display:flex;justify-content:space-between;margin:8px 0}
        .task-info-label{font-weight:600;color:#555}
        .task-info-value{color:#333}
        .priority-high{color:#dc3545;font-weight:bold}
        .priority-critical{color:#721c24;font-weight:bold}
        .priority-normal{color:#0d6efd}
        .priority-low{color:#6c757d}
        .footer{background:#faf9fc;padding:14px 24px;border-top:1px solid #ece9f5;font-size:11px;color:#8b8b9e}
    </style>
</head>
<body>
<center>
    <table class="wrap">
        <tr><td class="header"><img src="https://www.metallurgicabresciana.it/assets/img/logo18.png" class="logo"></td></tr>
        <tr><td class="content">
                <p class="meta">Task Management</p>
                <h1>Nuovo Task Assegnato</h1>
                <p style="margin:0 0 16px;color:#555;font-size:13px">
                    Ciao <strong>{{ $userName }}</strong>, ti è stato assegnato un nuovo task.
                </p>

                <div class="task-info">
                    <div class="task-info-row">
                        <span class="task-info-label">Codice:</span>
                        <span class="task-info-value">{{ $task->codice }}</span>
                    </div>
                    <div class="task-info-row">
                        <span class="task-info-label">Titolo:</span>
                        <span class="task-info-value">{{ $task->titolo }}</span>
                    </div>
                    <div class="task-info-row">
                        <span class="task-info-label">Priorità:</span>
                        <span class="task-info-value @if($task->priorieta == 4) priority-critical @elseif($task->priorieta == 3) priority-high @elseif($task->priorieta == 2) priority-normal @else priority-low @endif">
                            @if($task->priorieta == 4) Critico
                            @elseif($task->priorieta == 3) Alto
                            @elseif($task->priorieta == 2) Normale
                            @else Basso
                            @endif
                        </span>
                    </div>
                    <div class="task-info-row">
                        <span class="task-info-label">Data Scadenza:</span>
                        <span class="task-info-value">{{ $task->data_scadenza }}</span>
                    </div>
                    <div class="task-info-row">
                        <span class="task-info-label">Stato:</span>
                        <span class="task-info-value">
                            @if($task->stato == 1) Aperto
                            @elseif($task->stato == 2) Chiuso
                            @elseif($task->stato == 3) Da Approvare
                            @elseif($task->stato == 4) Sospeso
                            @elseif($task->stato == 5) In Svolgimento
                            @else Sconosciuto
                            @endif
                        </span>
                    </div>
                    @if($task->descrizione)
                    <div style="margin-top:12px">
                        <span class="task-info-label">Descrizione:</span>
                        <p style="margin:4px 0 0 0;color:#555;font-size:12px">{{ $task->descrizione }}</p>
                    </div>
                    @endif
                </div>

                <p style="margin:16px 0;color:#555;font-size:13px">
                    {{ $message }}
                </p>
            </td></tr>
        <tr><td class="footer">
                <strong>Metallurgica Bresciana S.p.A.</strong><br>
                Viale G. Marconi, 1 · 25020 Dello (BS)<br>
                <a href="https://www.metallurgicabresciana.it" style="color:#6c2bd9;text-decoration:none">www.metallurgicabresciana.it</a><br>
                Comunicazione automatica · Non rispondere a questa email
            </td></tr>
    </table>
</center>
</body>
</html>
