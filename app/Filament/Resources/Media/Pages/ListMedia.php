<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Models\MediaItem;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload Images')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    TextInput::make('name')
                        ->label('Name (optional)')
                        ->placeholder('e.g., Blog header images')
                        ->maxLength(255),
                    FileUpload::make('images')
                        ->label('Select Images')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->imageEditor()
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->storeFiles(false)
                        ->required()
                        ->helperText('Max 5MB per file. Supported: JPG, PNG, GIF, WebP'),
                ])
                ->action(function (array $data): void {
                    $files = collect($data['images'])->filter();

                    if ($files->isEmpty()) {
                        Notification::make()
                            ->title('No valid images to upload')
                            ->danger()
                            ->send();

                        return;
                    }

                    $mediaItem = MediaItem::create([
                        'name' => $data['name'] ?? 'Uploaded '.now()->format('Y-m-d H:i'),
                    ]);

                    $uploaded = 0;
                    foreach ($files as $file) {
                        $realPath = $file->getRealPath();
                        if (! $realPath) {
                            continue;
                        }
                        $mediaItem->addMedia($realPath)
                            ->usingFileName($file->getClientOriginalName())
                            ->toMediaCollection('images');
                        $uploaded++;
                    }

                    Notification::make()
                        ->title("Uploaded ".$uploaded." image(s) successfully")
                        ->success()
                        ->send();
                }),
        ];
    }
}
