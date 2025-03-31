const express = require('express');
const app = express();

app.get('/api/dates', (req, res) => {
    const now = new Date();

    // Generar fechas en el formato YYYY/MM/DD HH:mm:ss
    const dateTimeFrom = new Date(now.getFullYear(), now.getMonth(), now.getDate()).toISOString().replace('T', ' ').substring(0, 19).replace(/-/g, '/');
    const dateTimeTo = now.toISOString().replace('T', ' ').substring(0, 19).replace(/-/g, '/');

    res.json({
        dateTimeFrom: dateTimeFrom,
        dateTimeTo: dateTimeTo
    });
});

app.listen(3000, () => {
    console.log('Servidor Node.js corriendo en http://localhost:3000/api/dates');
});
