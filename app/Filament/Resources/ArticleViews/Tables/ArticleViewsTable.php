<?php

namespace App\Filament\Resources\ArticleViews\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArticleViewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('article'))
            ->columns([
                TextColumn::make('article.title')
                    ->label('Article')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('referer')
                    ->label('Referrer')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('viewed_at')
                    ->label('Viewed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('article')
                    ->relationship('article', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('viewed_at', 'desc');
    }
}
