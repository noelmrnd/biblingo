import { Share } from '@capacitor/share';
import { Capacitor } from '@capacitor/core';

export const ShareService = {
  async shareInviteCode(inviteCode, displayName = 'Tu amigo') {
    const inviteUrl = `https://app.libringo.com/invite/${inviteCode}`;
    const title = '¡Únete a mi racha de lectura en Libringo! 📖🔥';
    const text = `¡Hola! Te invito a formar parte de mi hábito diario de lectura en Libringo. Usa mi código de invitación: ${inviteCode}`;

    if (Capacitor.isNativePlatform()) {
      try {
        await Share.share({
          title,
          text,
          url: inviteUrl,
          dialogTitle: 'Compartir invitación de Libringo'
        });
        return { success: true, method: 'native' };
      } catch (e) {
        console.warn('Error en compartir nativo:', e);
      }
    }

    if (navigator.share) {
      try {
        await navigator.share({ title, text, url: inviteUrl });
        return { success: true, method: 'web-share' };
      } catch (e) {
        console.warn('Web Share omitido o cancelado:', e.message);
      }
    }

    // Fallback 1: Clipboard API (Si está en HTTPS o localhost)
    if (navigator.clipboard && navigator.clipboard.writeText) {
      try {
        await navigator.clipboard.writeText(`${text}\n${inviteUrl}`);
        return { success: true, method: 'clipboard' };
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
        return { success: true, method: 'clipboard' };
      }
    } catch (e) {
      console.warn('Error en fallback execCommand:', e);
    }

    return { success: false, method: 'none' };
  }
};
