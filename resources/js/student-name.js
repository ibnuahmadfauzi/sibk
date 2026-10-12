export const displayStudentName = (value) => String(value ?? '')
    .trim()
    .toLocaleLowerCase('id')
    .replace(/\p{L}[\p{L}\p{M}]*/gu, (word) =>
        word[0].toLocaleUpperCase('id') + word.slice(1));
