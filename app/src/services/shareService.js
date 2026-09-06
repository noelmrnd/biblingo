import { Share } from '@capacitor/share';
import { Capacitor } from '@capacitor/core';

async function copyText(text) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (e) {
      console.warn('Error en clipboard API:', e);
    }
  }

  // Fallback infalible en iOS Safari sobre HTTP / Red Local (sin saltos de viewport)
  try {
    const textArea = document.createElement('textarea');
    textArea.value = text;
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
    return successful;
  } catch (e) {
    console.warn('Error en fallback execCommand:', e);
    return false;
  }
}

export const ShareService = {
  async copyProfileLink(username) {
    const inviteUrl = `https://app.biblingo.me/invite/${username}`;
    const copied = await copyText(inviteUrl);
    return { success: copied };
  },

  async shareUsername(username) {
    const inviteUrl = `https://app.biblingo.me/invite/${username}`;
    const title = 'Biblingo • Lectura entre amigos 📖🔥';
    const text = `¡Hola! Te invito a Biblingo, lectura entre amigos. Sígueme: @${username}`;

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

    // Fallback: sin Web Share, copiar mensaje + enlace al portapapeles
    const copied = await copyText(`${text}\n${inviteUrl}`);
    return { success: copied, method: 'clipboard', canceled: false };
  }
};
