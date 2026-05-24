import sharp from "sharp";

await sharp("public/assets/hero-show-bg.jpg")
  .resize(2400)
  .webp({ quality: 72 })
  .toFile("public/assets/hero-show-bg.webp");

await sharp("public/assets/hero-show-bg.jpg")
  .resize(1200)
  .webp({ quality: 70 })
  .toFile("public/assets/hero-show-bg-mobile.webp");

console.log("Hero images optimised successfully.");