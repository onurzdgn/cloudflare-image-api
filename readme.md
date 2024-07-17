## Cloudflare Laravel Image

This package is the code that I used in a single project and has been edited to make it usable in many projects. Since I need these functions in more than one project, I have prepared and presented them as a package.

## Requirement

> Laravel >= 9
>
> Php >= 8.0

## Installation

You can install the package via composer:

```bash
$ composer require onurozdogan/cloudflare-image-api
```

## Usage

#### Configuration

First, you need to add the following configuration to your `.env` file.

```
CLOUDFLARE_API_KEY="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
CLOUDFLARE_ACCOUNT_ID="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

#### Upload Image
First parameter is image file, second parameter is image name.

`$image = CloudflareImageApi::upload($request->file('image'), $name);`

#### Response if image uploaded successfully:

```
if ($image->getStatusCode() == 200) {  // Check image uploaded successfully
    $img=$image->getData();            // Get image data 
    $db->image = $img->photoId;        // Save image id to database
}
```

#### Response if image uploaded failed:

```
if ($image->getStatusCode() != 200) {       // Check image uploaded failed
    $error=$image->getData();               // Get error message
    return response()->json($error, 400);   // Return error message (This is an example, you can use it as you wish)
}
```


#### Update Image
First parameter is image id (old image), second parameter is new image file, third parameter is image name

```$image = CloudflareImageApi::update($db->image, $request->file('image'), $name);```

#### Response if image updated successfully:

```
if ($image->getStatusCode() == 200) {  // Check image updated successfully
    $img=$image->getData();            // Get image data 
    $db->image = $img->photoId;        // Save image id to database
}
```

#### Response if image updated failed:

```
if ($image->getStatusCode() != 200) {       // Check image updated failed
    $error=$image->getData();               // Get error message
    return response()->json($error, 400);   // Return error message (This is an example, you can use it as you wish)
}
```

#### Delete Image

`$image = CloudflareImageApi::delete($db->image);`

#### Response if image deleted failed:

```
if ($image->getStatusCode() != 200) {       // Check image deleted failed
    $error=$image->getData();               // Get error message
    return response()->json($error, 400);   // Return error message (This is an example, you can use it as you wish)
}
```

#### Get Image In Blade

`<img src="https://imagedelivery.net/your-account-hash/{{ $blog->image }}/public" alt="{{ $blog->title }}" loading="lazy">`

## Security

If you discover any security related issues, please contact me via [https://onurozdogan.com](https://onurozdogan.com/contact). All security vulnerabilities will be fixed as soon as possible.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
