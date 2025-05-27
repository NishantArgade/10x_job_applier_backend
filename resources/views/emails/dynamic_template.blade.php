<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            background: #ffffff;
            margin: 0 auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #4caf50;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #555;
            line-height: 1.6;
        }

        .cta {
            margin-top: 20px;
            text-align: center;
        }

        .cta a {
            display: inline-block;
            text-decoration: none;
            background-color: #4caf50;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .cta a:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <div class="container">
        {!! $content !!}
    </div>
</body>

</html>