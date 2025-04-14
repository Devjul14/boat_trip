<?php

namespace App\Filament\Resources\BoatResource\Pages;

use App\Filament\Resources\BoatResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewBoat extends ViewRecord
{
    protected static string $resource = BoatResource::class;
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Boat Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('capacity'),
                        Infolists\Components\TextEntry::make('registration_number'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Boatman Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('boatman.name')
                            ->label('Boatman Name'),
                        Infolists\Components\TextEntry::make('boatman.email')
                            ->label('Boatman Email'),
                    ])
                    ->visible(fn ($record) => $record->boatman_id !== null)
                    ->columns(2),
                    
                Infolists\Components\Section::make('Created By')
                    ->schema([
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('User Name'),
                        Infolists\Components\TextEntry::make('creator.email')
                            ->label('User Email'),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}