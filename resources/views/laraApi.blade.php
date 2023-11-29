<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<body>

    


<table class=".table-striped">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">User ID</th>
        <th scope="col">Title</th>
        <th scope="col">Body</th>
      </tr>
    </thead>
    <tbody>
        @foreach ($response as $rs)
            <tr>
                <th scope="row">{{$rs->id}}</th>
                <td>{{$rs->userId}}</td>
                <td>{{$rs->title}}</td>
                <td>{{$rs->body}}</td>
            </tr>      
        @endforeach
      
    </tbody>
  </table>

</body>
</html>