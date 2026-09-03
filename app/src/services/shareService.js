import { Share } from '@capacitor/share';
import { Capacitor } from '@capacitor/core';

export const ShareService = {
  async shareInviteCode(inviteCode, displayName = 'Tu amigo') {
    const inviteUrl = `https://app.biblingo.me/invite/${inviteCode}`;
    const title = '¡Únete a mi racha de lectura en Biblingo! 📖🔥';
    const text = `¡Hola! Te invito a formar parte de mi hábito diario de lectura en Biblingo. Usa mi código de invitación: ${inviteCode}`;

    if (Capacitor.isNativePlatform()) {
      try {
        await Share.share({
          title,
          text,
          url: inviteUrl,
          dialogTitle: 'Compartir invitación de Biblingo'
        });
        return { success: true, method: 'native', canceled: false };
      } catch (e) {
        console.warn('Compartir nativo cancelado o falló:', e);
        return { success: false, method: 'native', canceled: true, error: e };
      }
    }

    if (navigator.share) {
      try {
        await navigator.share({ title, text, url: inviteUrl });
        return { success: true, method: 'web-share', canceled: false };
      } catch (e) {
        console.warn('Web Share omitido o cancelado:', e);
        const isCanceled = e.name === 'AbortError' || e.message?.toLowerCase().includes('cancel') || e.message?.toLowerCase().includes('user');
        if (isCanceled) {
          return { success: false, method: 'web-share', canceled: true, error: e };
        }
        return { success: false, method: 'web-share', canceled: false, error: e };
      }
    }

    // Fallback 1: Clipboard API (Si está en HTTPS o localhost)
    if (navigator.clipboard && navigator.clipboard.writeText) {
      try {
        await navigator.clipboard.writeText(`${text}\n${inviteUrl}`);
        return { success: true, method: 'clipboard', canceled: false };
      } catch (e) {
        console.warn('Error en clipboard API:', e);
      }
    }

    // Fallback 2: Infalible en iOS Safari sobre HTTP / Red Local (sin saltos de viewport)
    try {
      const shareContent = `${text}\n${inviteUrl}`;
      const textArea = document.createElement('textarea');
      textArea.value = shareContent;
      // Prevenir apertura de teclado virtual y saltos de viewport en iOS
      textArea.contentEditable = 'true';
      textArea.readOnly = true;
      textArea.style.position = 'absolute';
      textArea.style.left = '-9999px';
      textArea.style.top = `${window.scrollY || 0}px`;
      document.body.appendChild(textArea);
      
      const range = document.createRange();
      range.selectNodeContents(textArea);
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      textArea.setSelectionRange(0, 999999);

      const successful = document.execCommand('copy');
      document.body.removeChild(textArea);

      if (successful) {
        return { success: true, method: 'clipboard', canceled: false };
      }
    } catch (e) {
      console.warn('Error en fallback execCommand:', e);
    }

    return { success: false, method: 'none', canceled: false };
  }
};
