function generateURLImage(name) {
    if (!name || typeof name !== "string") return null;
  
    const inicial = name.trim()[0].toUpperCase();
    const bgHue = Math.floor(Math.random() * 360); // matiz aleatória
    const bgColor = `hsl(${bgHue}, 80%, 90%)`; // cor clara de fundo
    const textColor = `hsl(${bgHue}, 30%, 30%)`; // cor escura para contraste
  
    const bgHex = encodeURIComponent(rgbToHex(...hslToRgb(bgHue, 0.8, 0.9)));
    const textHex = encodeURIComponent(rgbToHex(...hslToRgb(bgHue, 0.3, 0.3)));
  
    return `https://placehold.co/400x400/${bgHex}/${textHex}?text=${inicial}`;
  }
  
  // Funções auxiliares
  function hslToRgb(h, s, l) {
    h /= 360;
    let r, g, b;
  
    if (s === 0) {
      r = g = b = l; // achromatic
    } else {
      const hue2rgb = (p, q, t) => {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
      };
  
      const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      const p = 2 * l - q;
      r = hue2rgb(p, q, h + 1 / 3);
      g = hue2rgb(p, q, h);
      b = hue2rgb(p, q, h - 1 / 3);
    }
  
    return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
  }
  
  function rgbToHex(r, g, b) {
    return [r, g, b]
      .map((x) => x.toString(16).padStart(2, "0"))
      .join("");
  }
  
  export default generateURLImage;