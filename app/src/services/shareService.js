import { Share } from '@capacitor/share';
import { Capacitor } from '@capacitor/core';

export const ShareService = {
  async shareInviteCode(inviteCode, displayName = 'Tu amigo') {
    const inviteUrl = `https://biblingo.me/invite/${inviteCode}`;
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
        // Usuario canceló o no soportado
      }
    }

    // Fallback: copiar al portapapeles
    try {
      await navigator.clipboard.writeText(`${text}\n${inviteUrl}`);
      return { success: true, method: 'clipboard' };
    } catch (e) {
      return { success: false, method: 'none' };
    }
  }
};
