
const  format_date = (date, format, locale) => {
  let options = {
    day: 'numeric',
    month: format,
    year: 'numeric',
  }
  if (typeof date === 'string') {
    date = new Date(date);
  }
  let intl = new Intl.DateTimeFormat(locale, options)
  return intl.format(date)
}

const format_time = (date, format, locale) => {
  let options = {
    hour: format === 'short' ? '2-digit' : format,
    minute: format === 'short' ? '2-digit' : format
  }
  if (format !== 'short') {
    options.second = format
  }

  if (typeof date === 'string') {
    date = new Date(date);
  }
  let intl = new Intl.DateTimeFormat(locale, options)
  return intl.format(date)
}

const format_datetime = (date, format, locale) => {
  if (typeof date === 'string') {
    date = new Date(date);
  }
  return datetimeformat.format_date(date, format, locale) + ' ' + datetimeformat.format_time(date, format, locale);
}

const localizeddate = (date, format, locale) => {
  return this.format_date(date, format, locale);
}

export { format_date, format_time, format_datetime, localizeddate }
