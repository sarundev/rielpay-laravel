<div class="rielpay-khqr" style="max-width:320px;margin:0 auto;font-family:Inter,'Kantumruy Pro',system-ui,sans-serif">
    <div style="overflow:hidden;border-radius:20px;background:#fff;box-shadow:0 10px 32px -12px rgba(15,23,42,.28);border:1px solid rgba(0,0,0,.05)">
        <div style="height:54px;background:#E1232E;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px;letter-spacing:.12em">KHQR</div>
        <div style="position:relative;padding:16px 26px 14px">
            <div style="position:absolute;right:0;top:0;border-top:32px solid #E1232E;border-left:32px solid transparent"></div>
            <div style="font-size:15px;color:#1f2937;padding-right:28px">{{ $merchant ?? config('app.name') }}</div>
            <div style="margin-top:6px;font-size:24px;font-weight:700;color:#000">{{ $amountText() }}</div>
        </div>
        <div style="border-top:2px dashed #cbd5e1"></div>
        <div style="padding:22px 28px 26px;text-align:center">
            @if ($qrImageUrl)
                <img src="{{ $qrImageUrl }}" alt="KHQR" width="264" height="264" style="display:block;width:100%;height:auto">
            @else
                <a href="{{ $payment->checkout_url }}" style="display:inline-block;padding:12px 18px;border-radius:10px;background:#111826;color:#fff;text-decoration:none;font-weight:600">Open payment page</a>
            @endif
        </div>
    </div>
    @if ($payment->deeplink)
        <a href="{{ $payment->deeplink }}" class="rielpay-deeplink" style="display:block;margin-top:14px;padding:12px;border-radius:10px;background:#111826;color:#fff;text-align:center;text-decoration:none;font-weight:600">Open in ABA Mobile</a>
    @endif
    <p style="margin-top:12px;text-align:center;font-size:13px;color:#64748b">Scan with any KHQR banking app · ស្កេនដោយកម្មវិធីធនាគារ</p>
</div>
