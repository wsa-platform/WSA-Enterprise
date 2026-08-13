import fs from 'node:fs'
import path from 'node:path'

const localesDir = path.join('src', 'i18n', 'locales')

const arWebsite = JSON.parse(fs.readFileSync(path.join(localesDir, 'website-ar.json'), 'utf8'))
const frWebsite = JSON.parse(fs.readFileSync(path.join(localesDir, 'website-fr.json'), 'utf8'))
const trWebsite = JSON.parse(fs.readFileSync(path.join(localesDir, 'website-tr.json'), 'utf8'))

for (const [lang, website] of [
  ['ar', arWebsite],
  ['fr', frWebsite],
  ['tr', trWebsite],
]) {
  const filePath = path.join(localesDir, `${lang}.json`)
  const locale = JSON.parse(fs.readFileSync(filePath, 'utf8'))
  locale.website = website
  fs.writeFileSync(filePath, `${JSON.stringify(locale, null, 2)}\n`)
}

console.log('Applied website translations for ar, fr, tr')
