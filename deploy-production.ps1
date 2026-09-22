param (
    [string]$LocalFolder = "G:\Meu Drive\Dev's\News-Caster Studio",
    [string]$FtpServer = "ftp://noticiabare.com/studio/",
    [string]$Username = "u576215103.noticiabare.com",
    [string]$Password = "?j9Bz8c;s:rmR!BJ"
)

function Create-FtpDirectory {
    param([string]$url)
    try {
        $request = [System.Net.FtpWebRequest]::Create($url)
        $request.Credentials = New-Object System.Net.NetworkCredential($Username, $Password)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $response = $request.GetResponse()
        $response.Close()
    } catch {}
}

function Upload-FtpFile {
    param([string]$localFile, [string]$ftpUrl)
    $webClient = New-Object System.Net.WebClient
    $webClient.Credentials = New-Object System.Net.NetworkCredential($Username, $Password)
    Write-Host "Enviando $localFile -> $ftpUrl"
    $webClient.UploadFile($ftpUrl, $localFile)
}

$files = Get-ChildItem -Path $LocalFolder -Recurse | Where-Object { $_.FullName -notmatch "\\\.git\\" }

foreach ($item in $files) {
    $relativePath = $item.FullName.Substring($LocalFolder.Length).TrimStart('\')
    $ftpUrl = $FtpServer + $relativePath.Replace('\', '/')
    
    if ($item.PSIsContainer) {
        Create-FtpDirectory -url $ftpUrl
    } else {
        Upload-FtpFile -localFile $item.FullName -ftpUrl $ftpUrl
    }
}
Write-Host "Deploy concluido com sucesso no servidor correto (noticiabare.com)!"
