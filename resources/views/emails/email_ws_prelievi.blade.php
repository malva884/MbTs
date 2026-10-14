<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Corso Lavori - Quantità Mancanti</h2>
    <p>{{ $content }}</p>
    @if(!empty($link))
        <p>
            <a href="{{ $link }}" style="display:inline-block;padding:10px 20px;background:#7367f0;color:#fff;text-decoration:none;border-radius:6px;">
                Apri Prelievi Mancanti
            </a>
        </p>
    @endif
    <p style="color:#999;font-size:12px;">Messaggio automatico da MbTs - non rispondere a questa email.</p>
</body>
</html>
